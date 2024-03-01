<?php

namespace ITUTUMedia\LaravelInstaller\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LaravelInstallerCommand extends Command
{
    public $signature = 'app:install 
                        {--set-env : Set all variables in .env file}
                        {--modules : Install as modules}
                        {--force : Force re-installation of the application}';

    public $description = 'Install the base system for the application';

    public function handle(): int
    {
        // Get the path to the .env file
        $envPath = base_path('.env');

        // Get the application key
        $key = config('app.key');

        // Check if .env file exists only once and store the result
        $envExists = file_exists($envPath);

        // Check if the .env file already exists
        if (!$envExists || $this->databaseNotConnected()) {
            // Output a message to the console
            $this->warn('The .env file does not exist or the database is not connected. It will set the .env file first.');

            // Set the --set-env option to true
            $this->input->setOption('set-env', true);
        }

        // Check if the .env file already exists
        if ($envExists && $this->confirm('Do you want to run full backup?')) {
            // If the user wants to re-install, create a backup of the current .env file
            $this->backup();
        }

        // Check if the .env file already exists
        if ($envExists && ($this->option('force') || $this->option('set-env'))) {

            // If the user wants to re-install, create a backup of the current .env file
            $this->warn('This will delete all your data in the database. A backup of the current .env file will be created. If you want to keep your data, cancel the installation and run the command without the --force option.');

            // If the user doesn't want to re-install, stop the installation
            if (! $this->confirm('Are you sure you want to continue?')) {

                // Output a message to the console
                $this->info('Installation aborted.');
                return self::SUCCESS;
            } else {

                // If the user wants to re-install, create a backup of the current .env file
                $this->backup();
            }
        }

        if ($this->option('set-env')) {
            // If the .env file doesn't exist, create it
            $this->info('Installing the application...');

            // Copy the .env.example file to .env
            copy(base_path('.env.example'), base_path('.env'));

            // Get the contents of the .env file
            $lines = file(base_path('.env'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            $this->info('===========================================');
            $this->info('Setting up the application...');

            // Loop through each line of the .env file
            foreach ($lines as $line) {

                // If the line starts with APP_ or DB_, prompt the user to enter a value for the variable
                if (preg_match('/^(APP_|DB_)/', $line)) {

                    // Get the field name and existing value (if any)
                    do {

                        // Get the field name and existing value (if any)
                        $field = explode('=', $line)[0];
                        $exist = str_replace('"', '', explode('=', $line)[1]);
                        $input = '';
                        $rules = ['nullable'];
                        if ($field == 'APP_ENV') {
                            $input = $this->choice(ucfirst($field), ['local', 'production', 'testing'], $exist == 'local' ? 0 : ($exist == 'production' ? 1 : 2));
                            $rules = ['required'];
                        } elseif ($field == 'APP_DEBUG') {
                            $input = $this->choice(ucfirst($field), ['true', 'false'], $exist == 'true' ? 0 : 1);
                            $rules = ['required'];
                        } elseif ($field == 'APP_URL') {
                            $input = $this->ask(ucfirst($field), $exist == '""' || $exist == '' ? 'http://localhost' : $exist);
                            $rules = ['required', 'url'];
                        } elseif ($field == 'APP_NAME') {
                            $input = $this->ask(ucfirst($field), $exist == '""' || $exist == '' ? 'Laravel' : $exist);
                            $rules = ['required'];
                        } elseif ($field == 'APP_KEY') {
                            $key = $input = $this->ask(ucfirst($field).' (leave blank to auto-generate)', $exist == '""' || $exist == '' ? '' : $exist);
                            $rules = ['nullable'];
                        } elseif ($field == 'DB_CONNECTION') {
                            $input = $this->choice(ucfirst($field), ['mysql', 'pgsql', 'sqlite', 'sqlsrv'], $exist == 'mysql' ? 0 : ($exist == 'pgsql' ? 1 : ($exist == 'sqlite' ? 2 : 3)));
                            $rules = ['required'];
                        } elseif ($field == 'DB_HOST') {
                            $input = $this->ask(ucfirst($field), $exist == '""' || $exist == '' ? 'localhost' : $exist);
                            $rules = ['required'];
                        } elseif ($field == 'DB_PORT') {
                            $input = $this->ask(ucfirst($field), $exist == '""' || $exist == '' ? '3306' : $exist);
                            $rules = ['required', 'numeric'];
                        } elseif ($field == 'DB_DATABASE') {
                            $input = $this->ask(ucfirst($field), $exist == '""' || $exist == '' ? 'forge' : $exist);
                            $rules = ['required'];
                        } elseif ($field == 'DB_USERNAME') {
                            $input = $this->ask(ucfirst($field), $exist == '""' || $exist == '' ? 'forge' : $exist);
                            $rules = ['required'];
                        } elseif ($field == 'DB_PASSWORD') {
                            $input = $this->secret(ucfirst($field));
                            $input = $input == '' ? $exist : $input;
                            $rules = ['nullable'];
                        } else {
                            if ($this->option('set-env')) {
                                $input = $this->ask(ucfirst($field), $exist == '""' || $exist == '' ? '' : $exist);
                                $rules = ['nullable'];
                            }
                        }

                        // Validate the input
                        $validator = $this->_validate([
                            $field => $input,
                        ], [
                            $field => $rules,
                        ]);

                        // Loop until the input passes validation
                    } while ($validator->fails());

                    // Replace the existing value with the validated value in the .env file
                    $validated = $validator->validated()[$field];
                    if (strpos($validated, ' ') !== false) {
                        $validated = '"'.$validated.'"';
                    }
                    $this->replaceInFile($line, $field.'='.$validated, base_path('.env'));
                }
            }
            $this->info('New value has been set, please run command php artisan app:install again');

            return self::SUCCESS;
        }

        if (empty($key)) {
            // Generate an application key
            $this->info('Generating application key...');
            $this->call('key:generate');
        }

        $this->info('===========================================');
        // Check if the .env file already exists
        if ($this->option('force') || $this->confirm('Do you want to migrate the database?')) {
            $this->info('Migrating database...');

            // If it does, ask the user if they want to re-install the application
            if ($this->confirm('Do you want to drop all tables first? This will delete all your data in the database.')) {
                // If the user doesn't want to re-install, stop the installation
                $this->call('migrate:fresh');
            } else {
                // Output a message to the console
                $this->call('migrate');
            }
        }

        $this->info('===========================================');
        // Check if the .env file already exists
        if (class_exists(\Laravel\Passport\PassportServiceProvider::class) && $this->confirm('Do you want to install Passport?')) {
            $this->info('Installing Passport...');
            $passport = [];

            // If it does, ask the user if they want to re-install the application
            if ($this->confirm('Do you want to generate encryption keys?')) {
                $passport = ['--uuids' => true];
            }

            // If the user doesn't want to re-install, stop the installation
            if ($this->confirm('Do you want to overwrite existing encryption keys?')) {
                $passport = ['--force' => true];
            }

            // Output a message to the console
            \Illuminate\Support\Facades\Artisan::call('passport:install', $passport);
            $passport = \Illuminate\Support\Facades\Artisan::output();
        }

        $this->info('===========================================');
        // Ask the user if they want to seed the database
        if ($this->option('force') || $this->confirm('Do you want to seed the database?')) {
            $this->info('Seeding database...');

            // Seed the database
            $this->call('db:seed');
        }

        if (($this->option('modules') || class_exists(\Nwidart\Modules\LaravelModulesServiceProvider::class)) && $this->confirm('Do you want to seed the modules?')) {
            $this->info('Seeding modules...');
            $this->call('module:seed');
        }

        $this->info('===========================================');
        // Ask the user if they want to create a user
        if (class_exists(\ITUTUMedia\LaravelMakeUser\LaravelMakeUser::class) && ($this->option('force') || $this->confirm('Do you want to create a user?'))) {
            $this->info('Creating user...');

            // Create a user
            $this->call('make:user', ['-S' => true]);
        }

        // Output a message to the console
        if (isset($passport)) {
            $this->info($passport);
        }
        $this->info('Installation complete. You can now run the application by visiting '.config('app.url').' in your browser.');

        return self::SUCCESS;
    }

    // Create a backup of the current .env file
    protected function backup()
    {
        $this->info('Re-installing the application...');
        $this->info('Creating backup of the current .env file...');

        // Create a backup of the current .env file
        copy(base_path('.env'), base_path('.env.backup.'.date('Y-m-d_H-i-s')));

        // Backup the database
        if (class_exists(\Spatie\Backup\BackupServiceProvider::class)) {
            $this->info('Backing up the database...');
            $this->call('backup:run', ['--only-db' => true]);
        }
    }

    // Replace the existing value with the validated value in the .env file
    protected function replaceInFile($search, $replace, $path)
    {
        // Get the contents of the file
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));
    }

    // Validate the input
    private function _validate($data, $rules): \Illuminate\Contracts\Validation\Validator
    {
        // Create a validator instance for the given data and rules
        $validator = \Illuminate\Support\Facades\Validator::make($data, $rules);

        // If the validator fails, output a warning with the first validation error message
        if ($validator->fails()) {
            $this->warn('Validation error: '.$validator->errors()->first());
        }

        return $validator;
    }
    
    // Check if the database is not connected
    protected function databaseNotConnected(): bool
    {
        try {
            DB::connection()->getPdo();
            return false;
        } catch (\Exception $e) {
            return true;
        }
    }
}
