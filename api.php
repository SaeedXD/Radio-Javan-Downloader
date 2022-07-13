<?php

if(isset($_GET['url']))
{
 	$result = radioJavan($_GET['url']); // array
	echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
}
else
{
	echo 'Enter URL.';
}


function radioJavan($url)
{
	$result_0 = xCurl([
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_COOKIEJAR      => 'RadioJavanCookie.txt',
		CURLOPT_HTTPHEADER     => [
			'accept: application/json, text/plain, */*',
			'Accept-Language: en-US,en;q=0.5',
			'x-application-type: WebClient',
			'x-client-version: 2.10.4',
			'Origin: https://www.google.com',
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.114 Safari/537.36',
		]
	]);
	
	preg_match('/og\:url.+\/(.+)"/', $result_0, $match);
	$id = $match[1];
		
	preg_match('/<meta name="csrf-token" content="(.+)"/U', $result_0, $m);
	$csrf = $m[1];

	$result_1 = xCurl([
		CURLOPT_URL => 'https://www.radiojavan.com/mp3s/mp3_host',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => "id=" . $id,
		CURLOPT_ENCODING       => 'gzip, deflate',
		CURLOPT_COOKIEFILE     => 'RadioJavanCookie.txt',
		CURLOPT_COOKIEJAR      => 'RadioJavanCookie.txt',
		CURLOPT_HTTPHEADER     => [
			'Authority: www.radiojavan.com',
			'Accept: application/json, text/javascript, */*; q=0.01',
			'Accept-Language: en-US,en;q=0.9',
			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
			'Origin: https://www.radiojavan.com',
			'Referer: https://www.radiojavan.com/mp3s/mp3/' . $id,
			'Sec-Fetch-Dest: empty',
			'Sec-Fetch-Mode: cors',
			'Sec-Fetch-Site: same-origin',
			'Sec-Gpc: 1',
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.114 Safari/537.36',
			'X-Csrf-Token: ' . $csrf,
			'X-Requested-With: XMLHttpRequest',
		]
	], true);
	
	$result_2 = xCurl([
		CURLOPT_URL => 'https://www.radiojavan.com/mp3s/mp3/' . $id . '?setup=1',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST           => true,
		CURLOPT_ENCODING       => 'gzip, deflate',
		CURLOPT_COOKIEFILE     => 'RadioJavanCookie.txt',
		CURLOPT_COOKIEJAR      => 'RadioJavanCookie.txt',
		CURLOPT_HTTPHEADER     => [
			'Authority: www.radiojavan.com',
			'Accept: application/json, text/javascript, */*; q=0.01',
			'Accept-Language: en-US,en;q=0.9',
			'Content-Length: 0',
			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
			'Origin: https://www.radiojavan.com',
			'Referer: https://www.radiojavan.com/mp3s/mp3/' . $id,
			'Sec-Fetch-Dest: empty',
			'Sec-Fetch-Mode: cors',
			'Sec-Fetch-Site: same-origin',
			'Sec-Gpc: 1',
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.114 Safari/537.36',
			'X-Csrf-Token: ' . $csrf,
			'X-Requested-With: XMLHttpRequest',
		]
	], true);

	$data = [];
	$data['music'] = $result_1['host'] . '/media/' . $result_2['currentMP3Url'] . '.' . $result_2['type'];
	$data['cover'] = preg_match('/image_src" href\="(.+\.jpg)/siU', $result_0, $match) ? $match[1] : false;
	$data['artist'] = preg_match('/songInfo.*span class="artist">(.+)<\/span/Us', $result_0, $match) ? $match[1] : false;
	$data['title'] = preg_match('/songInfo.*span class="song">(.+)<\/span/Us', $result_0, $match) ? $match[1] : false;
	$data['artist_fa'] = preg_match('/songInfo.*class="artist".*span class="artist">(.+)<\/span>/Us', $result_0, $match) ? $match[1] : false;
	$data['title_fa'] = preg_match('/songInfo.*span class="song" dir="rtl">(.+)<\/span>/Us', $result_0, $match) ? $match[1] : false;
	$data['text'] = preg_match('/<div class="lyricsFarsi text-center" dir="rtl">(.+)<\/div>/Us', $result_0, $match) ? fixText($match[1]) : false;
	$data['id'] = $id;
	$data['itemid'] = $result_2['itemid'];
	foreach ($result_2['related'] as $value) {
		$data['related'][] = $value['next'];
	}
	return $data;
}

function xCurl(array $options=[], $parseJson=false) :mixed
{
	$ch = curl_init();
	curl_setopt_array($ch, $options);
	$result = curl_exec($ch);
	curl_close($ch);
	if (curl_errno($ch)) {
		echo 'Error:' . curl_error($ch);
		return false;
	}
	if($parseJson && is_json($result))
		return json_decode($result, true);
	else
		return $result;
}

function is_json($string) {
	$data = @json_decode($string, true);
	return json_last_error() === JSON_ERROR_NONE ? $data : false;
}

function fixText($str)
{
	$str = preg_replace('/<br\s*\/?\s*>/Ui', "\n", trim($str));
	$ex = explode("\n", $str);
	$str = '';
	foreach($ex as $line)
	{
		$str .= trim($line) . "\n";
	}
	return substr($str, 0, -1);
}
