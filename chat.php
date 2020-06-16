<?php
	session_start();
	Header("Content-Type: application/json; charset=UTF-8");
	$resposta = new stdClass;
	try {
		unset($_SESSION['msg']);
		require('bd.php');
		if(isset($_POST['acao'])){
			switch($_POST['acao']){
				case 'verifica':
					$ult_msg = $_SESSION['ult_msg'];
					if($ult_msg == -1){
						$qry = mysqli_query($link, "SELECT id from mensagens order by id desc LIMIT 1");
						if($row = mysqli_fetch_array($qry, MYSQLI_ASSOC)){
							$ult_msg = $row['id'];
						}
					}
					$_SESSION['ult_ver'] = (new DateTime)->format("Y-m-d H:i:s");
					if($_SESSION['logado']){
						$login = mysqli_real_escape_string($link, $_SESSION['usuario']);
						mysqli_query($link, "UPDATE usuarios SET online=NOW() where lower(login)='$login'");
					} else {
						$login = 'global';
					}
					$qry = mysqli_query($link, "SELECT nome, login from usuarios where timestampdiff(SECOND, online, NOW())<3");
					$usuarios = [];
					while($row = mysqli_fetch_array($qry, MYSQLI_ASSOC)){
						$usuarios[] = $row;
					}
					$qry = mysqli_query($link, "SELECT * from mensagens where (id > $ult_msg) and (destino = 'global' or destino = '$login' or login = '$login')");
					$mensagens = [];
					while($row = mysqli_fetch_array($qry, MYSQLI_ASSOC)){
						$mensagens[] = $row;
						if($ult_msg < $row['id']){
							$ult_msg = $row['id'];
						}
					}
					$_SESSION['ult_msg'] = $ult_msg;
					$resposta->status = 'ok';
					$resposta->usuarios = $usuarios;
					$resposta->mensagens = $mensagens;
					break;
				case 'envia':
					if($_SESSION['logado']){
						$login = mysqli_real_escape_string($link, $_SESSION['usuario']);
						$nome = mysqli_real_escape_string($link, $_SESSION['nome']);
						$val = json_decode($_POST['val']);
						$destino = mysqli_real_escape_string($link, $val->para);
						$texto = mysqli_real_escape_string($link, $val->texto);
						$datahora = (new DateTime)->format("Y-m-d H:i:s");
						if(mysqli_query($link, "INSERT INTO mensagens (login, nome, destino, texto, datahora) VALUES ('$login', '$nome', '$destino', '$texto', timestamp('$datahora')) ")){
							$resposta->status = 'ok';
						} else {
							$resposta->status = 'erro';
							$resposta->msg = 'Erro ao enviar mensagem!';
						}
					} else {
						$resposta->status = 'erro';
						$resposta->msg = 'Você deve estar logado!';
					}
					break;
			}
		}
	}
	catch (Exception $e) {
		unset($resposta);
		$resposta = new stdClass;
		$resposta->status = 'erro';
		$resposta->msg = $e->getMessage();
	}
	echo(json_encode($resposta));
?>