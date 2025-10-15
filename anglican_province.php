<?php
function loadDiocese($dioceseName) {
    // Convert diocese name to filename format
    $filename = strtolower(str_replace([' ', "'", '’', '(', ')'], '_', $dioceseName));
    $filename = preg_replace('/_+/', '_', $filename); // Replace multiple underscores with single
    $filePath = __DIR__ . '/diocese/' . $filename . '.php';
    
    if (file_exists($filePath)) {
        include $filePath;
        return $diocese;
    }
    return null;
}

function getAllDioceses() {
    $dioceseNames = [
        'Diocese of Baringo',
        'Diocese of Bondo',
        'Diocese of Bungoma',
        'Diocese of Butere',
        'Diocese of Eldoret',
        'Diocese of Embu',
        'Diocese of Garissa',
        'Diocese of Kajiado',
        'Diocese of Katakwa',
        'Diocese of Kapsabet',
        'Diocese of Kericho',
        'Diocese of Kirinyaga',
        'Diocese of Kitale',
        'Diocese of Kitui',
        'Diocese of Machakos',
        'Diocese of Makueni',
        'Diocese of Malindi',
        'Diocese of Maralal',
        'Diocese of Marsabit',
        'Diocese of Maseno East',
        'Diocese of Maseno North',
        'Diocese of Maseno South',
        'Diocese of Maseno West',
        'Diocese of Mbeere',
        'Diocese of Meru',
        'Diocese of the Episcopate (All Saints’ Cathedral)',
        'Diocese of Mombasa',
        'Diocese of Mount Kenya Central',
        'Diocese of Mount Kenya South',
        'Diocese of Mount Kenya West',
        'Diocese of Mumias',
        'Diocese of Nakuru',
        'Diocese of Nambale',
        'Diocese of Nyahururu',
        'Diocese of Southern Nyanza',
        'Diocese of Taita Taveta',
        'Diocese of Thika',
        'Diocese of Upper Southern Nyanza'
    ];
    
    $allDioceses = [];
    foreach ($dioceseNames as $name) {
        $diocese = loadDiocese($name);
        if ($diocese) {
            $allDioceses[] = $diocese;
        }
    }
    return $allDioceses;
}

// Example usage:
// $baringoDiocese = loadDiocese('Diocese of Baringo');
// $allDioceses = getAllDioceses();
?>