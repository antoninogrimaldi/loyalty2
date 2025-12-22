<?php
// Generazione QR semplice usando Google Chart API (per evitare dipendenze pesanti in fase 1)
function qr_image_url($text)
{
    return 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($text);
}
