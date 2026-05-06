<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ModulePlaceholderController extends Controller
{
    public function __invoke(string $module): View
    {
        $config = config("modules.{$module}");

        if (! is_array($config)) {
            throw new NotFoundHttpException();
        }

        return view('modules.placeholder', [
            'module' => $module,
            'title' => $config['title'],
            'description' => $config['description'],
            'nextSteps' => $config['next_steps'],
        ]);
    }
}
