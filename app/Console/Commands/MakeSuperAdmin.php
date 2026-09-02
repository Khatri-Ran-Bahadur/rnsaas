<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

#[Signature('sathisaas:make-superadmin
    {email : The email address of the SuperAdmin}
    {--name= : The name of the SuperAdmin}
')]
#[Description('Create or promote a user as a SathiSaaS SuperAdmin')]
class MakeSuperAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        $validator = Validator::make(
            ['email' => $email],
            [
                'email' => [
                    'required',
                    'email',
                ],
            ],
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));

            return self::FAILURE;
        }

        $role = Role::query()
            ->where('name', 'SuperAdmin')
            ->where('guard_name', 'web')
            ->first();

        if (! $role) {
            $this->error(
                'The SuperAdmin role does not exist. Run the database seeders first.',
            );

            return self::FAILURE;
        }

        $user = User::query()
            ->where('email', $email)
            ->first();

        if ($user) {
            if ($user->hasRole('SuperAdmin')) {
                $this->warn(
                    "User {$user->email} is already a SuperAdmin.",
                );

                return self::SUCCESS;
            }

            $user->assignRole($role);

            $this->info(
                "SuperAdmin role assigned to {$user->email}.",
            );

            return self::SUCCESS;
        }

        $name = $this->option('name');

        if (! $name) {
            $name = $this->ask('SuperAdmin name');
        }

        $name = trim($name);

        if ($name === '') {
            $this->error('SuperAdmin name cannot be empty.');

            return self::FAILURE;
        }

        $password = $this->secret('SuperAdmin password');

        if (! $password) {
            $this->error('SuperAdmin password cannot be empty.');

            return self::FAILURE;
        }

        $passwordConfirmation = $this->secret(
            'Confirm SuperAdmin password',
        );

        if ($password !== $passwordConfirmation) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $user->assignRole($role);

        $this->newLine();

        $this->info('SathiSaaS SuperAdmin created successfully.');

        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $user->name],
                ['Email', $user->email],
                ['Role', 'SuperAdmin'],
            ],
        );

        return self::SUCCESS;
    }
}
