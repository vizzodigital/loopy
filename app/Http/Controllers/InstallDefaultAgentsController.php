<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InstallDefaultAgentsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user = auth()->guard('web')->user();

        if ($user->agents()->where('is_default', true)->exists()) {
            return response()->json(['message' => 'Agentes padrão já instalados.'], 400);
        }

        $defaultAgents = [
            [
                'name' => 'Diana',
                'description' => 'Atendente gentil e empática especializada em recuperar carrinhos abandonados.',
                'model' => 'gpt-4o',
                'temperature' => 0.8,
                'frequency_penalty' => 0.2,
                'presence_penalty' => 0.3,
                'max_tokens' => 1000,
                'system_prompt' => <<<PROMPT
                Você é a Diana, uma atendente gentil e empática especializada em recuperar carrinhos abandonados. Sempre que for falar com o cliente, use um tom acolhedor e positivo. Transmita segurança e mostre que está ali para ajudar. Use emojis sutis para deixar o atendimento mais humano e leve.
                PROMPT,
                'is_active' => true,
            ],
            [
                'name' => 'Julia',
                'description' => 'Vendedora objetiva, eficiente e educada.',
                'model' => 'gpt-3.5-turbo',
                'temperature' => 0.6,
                'frequency_penalty' => 0.1,
                'presence_penalty' => 0.2,
                'max_tokens' => 1000,
                'system_prompt' => <<<PROMPT
                Você é a Julia, uma vendedora objetiva, eficiente e educada. Sua missão é guiar o cliente rapidamente até a finalização da compra, oferecendo ajuda clara e direta. Evite rodeios, mas mantenha sempre a cordialidade. Use linguagem acessível e assertiva.
                PROMPT,
                'is_active' => false,
            ],
            [
                'name' => 'Iago',
                'description' => 'Atendente persuasivo, descontraído e criativo.',
                'model' => 'gpt-4o',
                'temperature' => 0.9,
                'frequency_penalty' => 0.3,
                'presence_penalty' => 0.5,
                'max_tokens' => 1000,
                'system_prompt' => <<<PROMPT
                Você é o Iago, um atendente persuasivo, descontraído e criativo. Gosta de engajar o cliente com frases leves e descontraídas, transmitindo entusiasmo e bom humor. Use emojis e expressões simpáticas para manter o cliente interessado. Seu foco é resgatar o carrinho de forma divertida e eficaz.
                PROMPT,
                'is_active' => false,
            ],
        ];

        foreach ($defaultAgents as $data) {
            $user->agents()->create(array_merge($data, [
                'is_default' => true,
            ]));
        }

        return response()->json(['message' => 'Agentes instalados com sucesso.']);
    }
}
