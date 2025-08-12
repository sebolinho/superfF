<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function show(Request $request, $slug)
    {
        $listing = Page::where('slug', $slug)->firstOrFail() ?? abort(404);
        
        // SEO
        $new = [$listing->title];
        $old = ['[title]'];

        $config['title'] = trim(str_replace($old, $new, trim(config('settings.page_title'))));
        $config['description'] = trim(str_replace($old, $new, trim(config('settings.page_description'))));

        return view('page.show', compact('config', 'listing', 'request'));
    }

    public function contact(Request $request)
    {
        // SEO
        $config['title'] = __('Contact').' - '.config('settings.title');
        $config['description'] = config('settings.description');

        return view('page.contact', compact('config', 'request'));
    }

    public function contactmail(Request $request)
    {
        // 1. Validação dos Dados do Formulário
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // 2. Sanitização dos Dados
        $name = $this->sanitizeInput($validatedData['name']);
        $subject = $this->sanitizeInput($validatedData['subject']);
        $message = $this->sanitizeInput($validatedData['message'], $allowLinks = true);

        // 3. Adicionar Timestamp
        $timestamp = now()->format('Y-m-d H:i:s');

        // 4. Formatar o Conteúdo do Arquivo
        $content = "Timestamp: {$timestamp}\n";
        $content .= "Nome: {$name}\n";
        $content .= "Assunto: {$subject}\n";
        $content .= "Mensagem:\n{$message}\n";
        $content .= str_repeat("=", 50) . "\n\n";

        // 5. Definir o Caminho da Pasta 'contato'
        $directory = storage_path('app/contato');

        // 6. Verificar se a Pasta Existe, Caso Contrário, Criar
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // 7. Definir o Nome do Arquivo com Base no Timestamp
        $filename = 'contato_' . now()->format('Ymd_His') . '.txt';
        $filePath = $directory . '/' . $filename;

        // 8. Salvar o Conteúdo no Arquivo
        file_put_contents($filePath, $content);

        // 9. Redirecionar com Mensagem de Sucesso
        return redirect()->route('contact')->with('success', __('Submitted'));
    }

    /**
     * Sanitiza a entrada do usuário.
     *
     * @param string $input
     * @param bool $allowLinks
     * @return string
     */
    private function sanitizeInput($input, $allowLinks = false)
    {
        // Remove tags HTML, exceto as permitidas
        if ($allowLinks) {
            // Permitir apenas <a> tags
            $allowedTags = '<a>';
            $input = strip_tags($input, $allowedTags);
        } else {
            // Remover todas as tags HTML
            $input = strip_tags($input);
        }

        // Convertendo caracteres especiais para entidades HTML para prevenir injeção
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

        // Opcional: Remover scripts ou outros conteúdos maliciosos
        // Aqui você pode adicionar mais regras de sanitização conforme necessário

        return $input;
    }
}
