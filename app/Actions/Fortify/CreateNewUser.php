<?php

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array  $input
     * @return \App\Models\User
     */
    public function create(array $input)
    {
        //dd($input);
        Validator::make($input, [
            'name'      =>  ['required', 'string' , 'max:150'],
            'username'  =>  ['required', 'string' , 'max:20' , 'unique:users'],
            'email'     =>  ['required', 'string' , 'email' , 'max:100', 'unique:users'],
            'dni'       =>  ['required', 'string' , 'unique:users' , 'min:13', 'max:13'],
            'phone'     =>  ['required', 'string' , 'unique:users' , 'max:12'],
            'institution'=> ['required', 'string' , 'max:255'],
            'password'  =>  $this->passwordRules(),
        ])->validate();



        return DB::transaction(function () use ($input) {
            return tap(User::create([
                'name' => $input['name'],
                'username' => $input['username'],
                'email' => $input['email'],
                'dni' => $input['dni'],
                'phone' => $input['phone'],
                'institution' => $input['institution'],
                'password'  => Hash::make($input['password']),
            ]), function (User $user) {
                $this->createTeam($user);
            });
        });
    }

    /**
     * Create a personal team for the user.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    protected function createTeam(User $user)
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));
    }
}
