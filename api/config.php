<?php
require_once dirname(__DIR__) . '/config/database.php';

// Base path da API - parte da URL antes de /api/
// Ex: http://localhost/Projetos/sanveg/api/... → use '/Projetos/sanveg'
defined('API_BASE_PATH') or define('API_BASE_PATH', '/Projetos/sanveg');
