<?php require 'config/config.php';
 if($_SERVER['REQUEST_METHOD']!=='POST') redirect('login.php');
  check_csrf(); $u=trim($_POST['usuario']??'');$p=$_POST['password']??'';
   $st=$pdo->prepare('SELECT id,nombre,apellido,usuario,password,rol,activo,edad FROM usuarios WHERE usuario=? LIMIT 1');
   $st->execute([$u]);$row=$st->fetch(); if(!$row||!$row['activo']||!verify_password($p,$row['password'])){flash('error','Usuario o contraseña incorrectos.');
   redirect('login.php');
   } 
   if($row['rol']==='cliente' && $row['edad']<18){flash('error','La cuenta no cumple la edad minima.');
   redirect('login.php');
   }
   session_regenerate_id(true);
    unset($row['password']);
     $_SESSION['user']=$row;
      redirect('dashboard.php');
