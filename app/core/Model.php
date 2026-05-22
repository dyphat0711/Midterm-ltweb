<?php
/**
 * AetherMind Base Model Class
 */

abstract class Model {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }
}
