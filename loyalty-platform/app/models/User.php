<?php
class User {
    public int $id;
    public string $email;
    public string $username;
    public string $phone;
    public string $password_hash;
    public bool $is_admin = false;
    public string $status;
    public ?string $email_verified_at;
}
