<?php

namespace App\Core;

class View
{
    public static function render(string $view, array $data = [], ?string $layout = null): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = base_dir() . '/app/Views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "View not found: {$view}";
            return;
        }

        if ($layout) {
            ob_start();
            include $viewFile;
            $content = ob_get_clean();
            $layoutFile = base_dir() . '/app/Views/' . $layout . '.php';
            include $layoutFile;
        } else {
            include $viewFile;
        }
    }

    public static function partial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = base_dir() . '/app/Views/' . $view . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        }
    }
}
