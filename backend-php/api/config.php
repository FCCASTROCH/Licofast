<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$host='127.0.0.1'; $db='licofast'; $user='root'; $pass='';
try {
  $pdo=new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4",$user,$pass,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false
  ]);
} catch(Throwable $e) { http_response_code(500); echo json_encode(['error'=>'No se pudo conectar a MySQL']); exit; }
function body(): array { $raw=file_get_contents('php://input'); $data=json_decode($raw ?: '[]',true); return is_array($data)?$data:[]; }
function out($data,int $status=200): never { http_response_code($status); echo json_encode($data,JSON_UNESCAPED_UNICODE); exit; }
function auth(): array { $h=$_SERVER['HTTP_AUTHORIZATION']??''; if(!preg_match('/Bearer\s+(.+)/i',$h,$m)) out(['error'=>'Token requerido'],401); $p=explode('|',$m[1],2); if(count($p)!==2) out(['error'=>'Token inválido'],401); return ['id'=>(int)$p[0],'rol'=>$p[1]]; }
function requireRole(array $roles): array { $u=auth(); if(!in_array($u['rol'],$roles,true)) out(['error'=>'Acceso denegado'],403); return $u; }
function hashPass(string $password): string { $saltBytes=random_bytes(16); $saltB64=base64_encode($saltBytes); $iterations=120000; $hash=hash_pbkdf2('sha256',$password,$saltBytes,$iterations,32,true); return 'PBKDF2-SHA256$'.$iterations.'$'.$saltB64.'$'.base64_encode($hash); }
function verifyPass(string $password,string $stored): bool { $p=explode('$',$stored); if(count($p)!==4 || $p[0]!=='PBKDF2-SHA256') return password_verify($password,$stored); $salt=base64_decode($p[2]); $h=hash_pbkdf2('sha256',$password,$salt,(int)$p[1],32,true); return hash_equals(base64_decode($p[3]),$h); }
