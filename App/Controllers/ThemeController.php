<?php

namespace App\Controllers;

use App\Models\Theme;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ThemeController extends \Leaf\ApiController
{
	public function all() {
        $themes = Theme::all();
        response($themes);
	}

    public function one($id) {
        try {
            $theme = Theme::findOrFail($id);
            response($theme);
        }
        catch (ModelNotFoundException) {
            throwErr("Theme not found", 404);
        }
    }
}