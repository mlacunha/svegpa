<?php
/**
 * Configurações de aplicação e deploy.
 *
 * Problemas comuns após deploy:
 *
 * 1) Login funciona mas volta erro / redireciona para login de novo:
 *    - Cookie de sessão sem Secure em HTTPS. O script detecta HTTPS automaticamente
 *    - Se estiver atrás de proxy, confira HTTP_X_FORWARDED_PROTO
 *
 * 2) Links/redirects quebrados (404, caminho errado):
 *    - Defina SANVEG_BASE_PATH no bloco abaixo. Ex: '/sanveg/' se a app está em domain.com/sanveg/
 *
 * 3) Banco remoto inacessível do servidor web:
 *    - Libere o IP do servidor no firewall do MySQL
 *    - Em config/database.php, host/user/password devem estar corretos
 */
defined('SANVEG_CONFIG_LOADED') or define('SANVEG_CONFIG_LOADED', true);

// Força HTTPS para cookies de sessão quando atrás de proxy (ex: Cloudflare, load balancer)
// Descomente se o login falha após redirect em produção HTTPS
if (!defined('SANVEG_FORCE_SECURE_COOKIE')) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
    define('SANVEG_FORCE_SECURE_COOKIE', $isHttps);
}

// Override do base path quando getBasePath() falha (ex: subpasta, proxy, rewrite)
// Ex: define('SANVEG_BASE_PATH', '/sanveg/'); ou define('SANVEG_BASE_PATH', '/');
if (!defined('SANVEG_BASE_PATH')) {
    define('SANVEG_BASE_PATH', null);
}
