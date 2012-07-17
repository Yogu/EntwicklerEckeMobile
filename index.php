<?php

require_once('HttpRequest.php');

define('HOST', 'www.entwickler-ecke.de');

function sendFile($fileName, $useCache = true) {
	if (!file_exists($fileName))
		throw new Exception('File does not exist: '.$filewName);
	$contentType = fileNameToMime($fileName);
	if (substr($contentType, 0, strlen('text/')) == 'text/')
		$contentType .= '; charset=utf-8';

	if ($useCache) {
		// Datei nur senden, wenn seit dem letzen Aufruf geändert
		$fileTime = filemtime($fileName);
		$lastLoad = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ?
		strtotime(trim($_SERVER['HTTP_IF_MODIFIED_SINCE'])) : false;
		if (!strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'msie 6.0')) {
			// Aktuelle Version der Datei muss im Cache liegen und sie darf nicht
			// innerhalb der letzten 10 Sekunden bearbeitet worden sein
			if ($lastLoad && $lastLoad == $fileTime && $fileTime + 10 < time()) {
				if (@php_sapi_name() === 'CGI')
					header('Status: 304 Not Modified', true, 304);
				else
					header('HTTP/1.0 304 Not Modified', true, 304);

				// seems that we need those too ... browsers
				header('Pragma: public');
				header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T',
						time() + 60));
				return true;
			} else {
				header('Last-Modified: '.
						gmdate('D, d M Y H:i:s', $fileTime) . ' GMT');
			}
		}
	}

	// Datei und Header senden
	header("Content-Type: $contentType");
	header("Content-Length: " . filesize($fileName));
	header("Content-Disposition: inline");
	readfile($fileName);

	return true;
}

function getFileExtension($fileName) {
	$pathInfo = pathinfo($fileName);
	return strtolower(isset($pathInfo['extension']) ? $pathInfo['extension'] : '');
}

function fileNameToMime($fileName) {
	switch(getFileExtension($fileName)) {
		// Documents
		case 'txt': return 'text/plain';
		case 'rtx': return 'text/richtext';
		case 'rtf': return 'text/rtf';
		case 'html':
		case 'htm':
		case 'php':
			return 'text/html';
		case 'css':
			return 'text/css';
		case 'js':
			return 'text/javascript';
		case 'pdf': return 'application/pdf';
		case 'dot': return 'application/msword';
		case 'doc': return 'application/msword';
		case 'dotx': return 'application/msword';
		case 'docx': return 'application/msword';
		case 'xls': return 'application/msexcel';
		case 'xlsx': return 'application/msexcel';
		case 'ppt': return 'application/mspowerpoint';
		case 'pps': return 'application/mspowerpoint';
		case 'ppz': return 'application/mspowerpoint';
		case 'pot': return 'application/mspowerpoint';
		case 'pptx': return 'application/mspowerpoint';
		case 'ppsx': return 'application/mspowerpoint';
		case 'ppzx': return 'application/mspowerpoint';
		case 'potx': return 'application/mspowerpoint';

		// Images
		case 'gif': return 'image/gif';
		case 'jpeg': return 'image/jpeg';
		case 'jpg': return 'image/jpeg';
		case 'jpe': return 'image/jpeg';
		case 'png': return 'image/png';
		case 'tiff': return 'image/tiff';
		case 'tif': return 'image/tiff';
		case 'ico': return 'image/x-icon';

		// Videos
		case 'avi': return 'video/avi';
		case 'flv': return 'video/x-flv';
		case 'mpg': return 'video/mpeg';
		case 'mp4': return 'video/mp4';
		case 'mov': return 'video/quicktime';
		case 'wmv': return 'video/x-ms-wmv';

		default: return 'application/octet-stream';
	}
}

function sendWebDocument($url) {
	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		switch (getFileExtension($url)) {
			case 'png':
			case 'gif':
			case 'jpg':
			case 'jpeg':
			case 'css':
			case 'js':
				redirectToDocument($url);
				exit;
		}
	}

	$host = HOST;

	$ip = gethostbyname($host);
	$method = $_SERVER['REQUEST_METHOD'];
	$protocol = $_SERVER['SERVER_PROTOCOL'];
	$headers = new HttpRequest();
	$headers = $headers->raw();
	$headers = str_replace('http://'.$_SERVER['HTTP_HOST'], 'http://'.$host, $headers);
	$body = file_get_contents('php://input');

	$request = "$method $url $protocol\r\n".
			"Host: $host\r\n".
			"Connection: Close\r\n".
			"$headers\r\n\r\n".
			$body;

	// Send request, get response
	$socket = fsockopen($ip, 80, $errno, $errstr);
	if ($socket) {
		fputs($socket, $request);
		$response = '';
		while (!feof($socket)) {
			$response .= fread($socket, 1024 * 64);
			$lastTime = microtime(true);
		}
		fclose($socket);
	} else
		throw new RuntimeException('Failed to open socket: '.$errstr.' ('.$errno.'), url: '.$url);

	$response = explode("\r\n\r\n", $response, 2);
	$headerString = $response[0];
	$headerString = str_replace('http://'.$host, 'http://'.$_SERVER['HTTP_HOST'], $headerString);
	$body = count($response) == 2 ? $response[1] : '';

	$headers = explode("\r\n", $headerString);
	foreach ($headers as $header) {
		if (strpos(strtolower($header), "transfer-encoding: chunked") === false)
			header($header);
	}

	if ($body) {
		if (strpos(strtolower($headerString), "transfer-encoding: chunked") !== false)
			$body = http_chunked_decode($body);
		$body = str_replace('http://'.$host, 'http://'.$_SERVER['HTTP_HOST'], $body);
		
		if (strpos($body, '</head>') !== false && file_exists('head.html')) {
			$htmlHead = file_get_contents("head.html");
			$body = str_replace('</head>', $htmlHead.'</head>', $body);
		}

		echo $body;
	}
}

if (!function_exists('http_chunked_decode')) {
	/**
	 * dechunk an http 'transfer-encoding: chunked' message
	 *
	 * @param string $chunk the encoded message
	 * @return string the decoded message.  If $chunk wasn't encoded properly it will be returned unmodified.
	 */
	function http_chunked_decode($chunk) {
		$pos = 0;
		$len = strlen($chunk);
		$dechunk = null;

		while(($pos < $len)
				&& ($chunkLenHex = substr($chunk,$pos, ($newlineAt = strpos($chunk,"\n",$pos+1))-$pos)))
		{
			if (! is_hex($chunkLenHex)) {
				trigger_error('Value is not properly chunk encoded', E_USER_WARNING);
				return $chunk;
			}

			$pos = $newlineAt + 1;
			$chunkLen = hexdec(rtrim($chunkLenHex,"\r\n"));
			$dechunk .= substr($chunk, $pos, $chunkLen);
			$pos = strpos($chunk, "\n", $pos + $chunkLen) + 1;
		}
		return $dechunk;
	}
	
	/**
	 * determine if a string can represent a number in hexadecimal
	 *
	 * @param string $hex
	 * @return boolean true if the string is a hex, otherwise false
	 */
	function is_hex($hex) {
		// regex is for weenies
		$hex = strtolower(trim(ltrim($hex,"0")));
		if (empty($hex)) {
			$hex = 0;
		};
		$dec = hexdec($hex);
		return ($hex == dechex($dec));
	}

}

function redirectToDocument($url) {
	header('Location: http://'.HOST.$url);
}

function run() {
	$file = $_SERVER['REQUEST_URI'];
	if (strlen($file) > 1 && strpos($file, '..') === false && $file[0] == '/') {
		$fileName = dirname($_SERVER['SCRIPT_FILENAME']).'/overrides'.$file;
		if (file_exists($fileName)) {
			if (getFileExtension($fileName) == 'php')
				include($fileName);
			else
				sendFile($fileName);
			exit;
		}
	}

	sendWebDocument($file);
}

run();
