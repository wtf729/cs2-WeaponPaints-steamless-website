<?php

function h($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

set_exception_handler(static function (Throwable $exception) {
	error_log(sprintf(
		"Unhandled %s: %s in %s:%d\n%s",
		get_class($exception),
		$exception->getMessage(),
		$exception->getFile(),
		$exception->getLine(),
		$exception->getTraceAsString()
	));
	if (!headers_sent()) {
		http_response_code(500);
		header('Content-Type: text/plain; charset=utf-8');
	}
	echo UtilsClass::currentLanguage() === 'en'
		? 'An unexpected server error occurred. Please try again later.'
		: '服务器发生意外错误，请稍后重试。';
});

function go($url)
{
	header("Location: {$url}");
	exit;
}

function queueFloatingNotice($translationKey)
{
	$translationKey = trim((string)$translationKey);
	if ($translationKey !== '') {
		$_SESSION['cs2_floating_notice'] = $translationKey;
	}
}

function pullFloatingNoticeKey()
{
	$translationKey = trim((string)($_SESSION['cs2_floating_notice'] ?? ''));
	unset($_SESSION['cs2_floating_notice']);
	return $translationKey;
}

function languageUrl($language)
{
	$query = $_GET;
	$query['lang'] = $language;
	return 'index.php?' . http_build_query($query);
}
