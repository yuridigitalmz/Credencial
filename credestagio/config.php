<?php
// ===== Configuração — ajuste à sua máquina (XAMPP/WAMP: root sem senha) =====
const DB_HOST = 'localhost';
const DB_NAME = 'credestagio';
const DB_USER = 'root';
const DB_PASS = '';

const APP_NOME     = 'CredEstágio';
const INSTITUICAO  = 'Universidade Licungo';
// Endereço público do site (ex.: https://estagio.ul.ac.mz). Vazio = deduzido do pedido. Usado nos e-mails e no código QR.
const SITE_URL = '';
// Exigir que as cadeiras do 1.º ao 3.º ano estejam aprovadas para pedir credencial
const EXIGIR_DISCIPLINAS = true;
// Localidade impressa na data da credencial ("Dondo, 20 / 08 / 2026")
const LOCAL_EMISSAO = 'Dondo';
date_default_timezone_set('Africa/Maputo');
const INACTIVIDADE = 20 * 60; // terminar sessão após 20 min sem actividade

// Caminho base calculado automaticamente (funciona na raiz ou numa subpasta)
$__app = str_replace('\\', '/', __DIR__);
$__doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
define('BASE_URL', ($__doc !== '' && stripos($__app, $__doc) === 0) ? rtrim(substr($__app, strlen($__doc)), '/') : '');
