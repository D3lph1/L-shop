<?php
declare(strict_types = 1);

namespace App\Models\User;

use Cartalyst\Sentinel\Users\UserInterface as BaseUserInterface;

interface UserInterface extends BaseUserInterface
{
    public function getActivations(): iterable;


    public function getId(): int;

    public function getUsername(): string;

    public function getEmail(): string;

    public function getPassword(): string;

    public function getBalance(): float;
}