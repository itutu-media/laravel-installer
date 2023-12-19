<?php

namespace ITUTUMedia\LaravelInstaller\Commands;

use Illuminate\Console\Command;

class LaravelInstallerCommand extends Command
{
    public $signature = 'app:install {--all-variable : Set all variables in .env file}
    {--force : Force re-installation of the application}';

    public $description = 'Install the base system for the application';

    public function handle(): int
    {
        // Check if the .env file already exists
        if (file_exists(base_path('.env')) && ! $this->option('force')) {
            // If it does, ask the user if they want to re-install the application
            $this->warn('The .env file already exists. Are you sure you want to re-install the application?');

            // If the user doesn't want to re-install, stop the installation
            if (! $this->confirm('This will create backups of the current .env file and create a new one.')) {

                // Output a message to the console
                return $this->info('Installation aborted.');
            } else {

                // If the user wants to re-install, create a backup of the current .env file
                $this->backup();
            }

        // If the .env file doesn't exist, create it
        } elseif (file_exists(base_path('.env')) && $this->option('force')) {

            // If the user wants to re-install, create a backup of the current .env file
            $this->warn('The .env file already exists. This will delete all your data in the database. A backup of the current .env file will be created. If you want to keep your data, cancel the installation and run the command without the --force option.');

            // If the user doesn't want to re-install, stop the installation
            if (! $this->confirm('Are you sure you want to continue?')) {

                // Output a message to the console
                return $this->info('Installation aborted.');
            } else {

                // If the user wants to re-install, create a backup of the current .env file
                $this->backup();
            }
        } else {

            // If the .env file doesn't exist, create it
            $this->info('Installing the application...');

            // Copy the .env.example file to .env
            copy(base_path('.env.example'), base_path('.env'));
        }

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
                        $input = $this->secret(ucfirst($field), $exist == '""' || $exist == '' ? '' : $exist);
                        $rules = ['nullable'];
                    } else {
                        if ($this->option('all-variable')) {
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
            if ($this->confirm('Do you want to drop all tables first?')) {
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

        $this->info('===========================================');
        // Ask the user if they want to create a user
        if (class_exists(\ItutuMedia\LaravelMakeUser\CreateUserServiceProvider::class) && ($this->option('force') || $this->confirm('Do you want to create a user?'))) {
            $this->info('Creating user...');

            // Create a user
            $this->call('make:user', ['-S' => true]);
        }

        // Output a message to the console
        if (isset($passport)) {
            $this->info('\n'.$passport);
        }
        $this->info('Installation complete. You can now run the application by visiting '.config('app.url').' in your browser.');

        return self::SUCCESS;
    }

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

    protected function replaceInFile($search, $replace, $path)
    {
        // Get the contents of the file
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));
    }

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
}