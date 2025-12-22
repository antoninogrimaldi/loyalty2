<?php
class AuditLog {
    public int $id;
    public int $actor_user_id;
    public string $action;
    public string $entity;
}
