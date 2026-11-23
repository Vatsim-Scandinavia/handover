<?php
namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The Edit toggle on the group page. It starts on after a save from the page,
 * so a series of edits stays in edit mode. It also starts on after a failed
 * validation, so the errored form is editable.
 */
class EditMode
{
    private const string KEY = 'editing';

    public static function keep(RedirectResponse $redirect): RedirectResponse
    {
        return $redirect->with(self::KEY, true);
    }

    public static function isOn(Request $request): bool
    {
        return $request->session()->has('errors') || $request->session()->get(self::KEY, false);
    }
}
