<?php
function panelTitleBadge($title) {
    if (empty($title)) return '';
    $colors = [
        'EE'  => ['#dbeafe', '#1d4ed8'],
        'PA'  => ['#dcfce7', '#166534'],
        'IAP' => ['#fef3c7', '#92400e'],
        'IA'  => ['#f3e8ff', '#6b21a8'],
        'EA'  => ['#ffe4e6', '#9f1239'],
    ];
    [$bg, $color] = $colors[$title] ?? ['#f3f4f6', '#374151'];
    return "<span style='background:{$bg};color:{$color};font-size:10px;
        padding:2px 8px;border-radius:999px;font-weight:700;
        white-space:nowrap'>{$title}</span>";
}