<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceDocumentController extends Controller
{
    public function show(string $type, string $id): StreamedResponse
    {
        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->isInternal(), 403);

        $path = match ($type) {
            'expense' => Expense::findOrFail($id)->document_path,
            'income'  => Income::findOrFail($id)->document_path,
            'payment' => Payment::findOrFail($id)->document_path,
            default   => abort(404),
        };

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $mime = Storage::disk('local')->mimeType($path);
        $name = basename($path);

        return Storage::disk('local')->response($path, $name, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . $name . '"',
            'X-Robots-Tag'        => 'noindex',
        ]);
    }
}
