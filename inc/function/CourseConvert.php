<?
function courseTo(?float $amount, ?string $currencyIn, ?string $currencyOut = 'PLN', $courseArr = null){
	$course = $_ENV[PROJECT]['get_course'] ?? get_course() ;
	
	switch ($currencyIn) {

		case 'PLN':
			return match ($currencyOut) {
				'PLN' => 1 * $amount,
				'USD', 'USDT', 'USDC' => (1 / $course['USDPLN']) * $amount,
				'EUR' => (1 / $course['EURPLN']) * $amount,
				'GBP' => (1 / $course['GBPPLN']) * $amount,
				'BTC' => (1 / $course['BTC']) * $amount,
				'ETH' => (1 / $course['ETH']) * $amount,
				default => null,
			};	
			break;

		case 'USD':
		case 'USDC':
		case 'USDT':
		case 'BUSD':
		case 'TUSD':
		case 'PAX':
		case 'DAI':
			return match ($currencyOut) {
				'PLN' => ($course['USDPLN']) * $amount,
				'USD', 'USDT', 'USDC' => 1 * $amount,
				'EUR' => ($course['USDPLN'] / $course['EURPLN']) * $amount,
				'GBP' => ($course['USDPLN'] / $course['GBPPLN']) * $amount,
				'BTC' => ($course['USDPLN'] / $course['BTC']) * $amount,
				'ETH' => ($course['USDPLN'] / $course['ETH']) * $amount,
				default => null,
			};	
			break;

		case 'EUR':
			return match ($currencyOut) {
				'PLN' => ($course['EURPLN']) * $amount,
				'USD', 'USDT', 'USDC' => ($course['EURPLN'] / $course['USDPLN']) * $amount,
				'EUR' => 1 * $amount,
				'GBP' => ($course['EURPLN'] / $course['GBPPLN']) * $amount,
				'BTC' => ($course['EURPLN'] / $course['BTC']) * $amount,
				'ETH' => ($course['EURPLN'] / $course['ETH']) * $amount,
				default => null,
			};	
			break;
			
		case 'BTC':
			return match ($currencyOut) {
				'PLN' => ($course['BTC']) * $amount,
				'USD', 'USDT', 'USDC' => ($course['BTC'] / $course['USDPLN']) * $amount,
				'EUR' => ($course['BTC'] / $course['EURPLN']) * $amount,
				'GBP' => ($course['BTC'] / $course['GBPPLN']) * $amount,
				'BTC' => 1 * $amount,
				//'ETH' => 1 * $amount,
				default => null,
			};		
			break;

		case 'ETH':
			return match ($currencyOut) {
				'PLN' => ($course['ETH']) * $amount,
				'USD', 'USDT', 'USDC' => ($course['ETH'] / $course['USDPLN']) * $amount,
				'EUR' => ($course['ETH'] / $course['EURPLN']) * $amount,
				'GBP' => ($course['ETH'] / $course['GBPPLN']) * $amount,
				//'BTC' => ($course['GBPPLN'] / $course['BTC']) * $amount,
				'ETH' => 1 * $amount,
				default => null,
			};
			break;

		case 'GBP':
			return match ($currencyOut) {
				'PLN' => $course['GBPPLN'] * $amount,
				'USD', 'USDT', 'USDC' => ($course['GBPPLN'] / $course['USDPLN']) * $amount,
				'EUR' => ($course['GBPPLN'] / $course['EURPLN']) * $amount,
				'GBP' => 1 * $amount,
				'BTC' => ($course['GBPPLN'] / $course['BTC']) * $amount,
				'ETH' => ($course['GBPPLN'] / $course['ETH']) * $amount,
				default => null,
			};
			break;
		
	}
}