<?php

declare(strict_types = 1);

namespace App\Filament\Pages\Tenancy;

use App\Enums\IntegrationTypeEnum;
use App\Enums\PlatformsEnum;
use App\Models\Platform;
use App\Models\Store;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Support\Str;

class RegisterTeam extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Criar nova loja';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Loja')
                    ->suffix('.myshopify.com')
                    ->required()
                    ->maxLength(50),

                // Select::make('ecommerce')
                //     ->label('Plataforma de e-commerce')
                //     ->required()
                //     ->options(Platform::where('is_active', true)->where('type', IntegrationTypeEnum::ECOMMERCE)->pluck('name', 'id')),

                // Select::make('ai')
                //     ->label('Inteligência Artificial')
                //     ->required()
                //     ->options(Platform::where('is_active', true)->where('type', IntegrationTypeEnum::AI)->pluck('name', 'id')),

                // Select::make('whatsapp')
                //     ->label('Plataforma de comunicação')
                //     ->options(Platform::where('is_active', true)->where('type', IntegrationTypeEnum::WHATSAPP)->pluck('name', 'id')),
            ]);
    }

    protected function handleRegistration(array $data): Store
    {
        $store = Store::create([
            'name' => $data['name'],
            'slug' => (string) Str::uuid(),
            'description' => '',
            'is_active' => true,
            'plan_id' => 1,
        ]);

        $store->integrations()->create([
            'platform_id' => PlatformsEnum::SHOPIFY->value,
            'webhook' => Str::uuid()->toString(),
            'type' => IntegrationTypeEnum::ECOMMERCE,
            'configs' => ['shop' => $data['name'] . '.myshopify.com'],
            'is_active' => false,
        ]);

        $store->integrations()->create([
            'platform_id' => PlatformsEnum::OPENAI->value,
            'webhook' => Str::uuid()->toString(),
            'type' => IntegrationTypeEnum::AI,
            'configs' => ['api_key' => null],
            'is_active' => false,
        ]);

        $configPresets = [
            7 => ['phone_number_id' => null, 'waba_id' => null, 'access_token' => null],
            8 => ['instance' => null, 'api_token' => null, 'security_token' => null],
            9 => ['session' => null, 'api_key' => null],
        ];

        $configs = $configPresets[PlatformsEnum::WHATSAPP->value] ?? [];

        $store->integrations()->create([
            'platform_id' => PlatformsEnum::WHATSAPP->value,
            'webhook' => Str::uuid()->toString(),
            'type' => IntegrationTypeEnum::WHATSAPP,
            'configs' => $configs,
            'is_active' => false,
        ]);

        $store->users()->attach(auth()->guard('web')->user());

        $defaultAgents = [
            [
                'name' => 'Diana',
                'description' => 'Atendente gentil e empática especializada em recuperar carrinhos abandonados.',
                'model' => 'gpt-4o-mini',
                'temperature' => 0.4,
                'top_p' => 1,
                'frequency_penalty' => 0.3,
                'presence_penalty' => 0,
                'max_tokens' => 800,
                'system_prompt' => <<<PROMPT
                    ## CONTEXTO
                    Você é a Diana, uma assistente virtual simpática, gentil e especializada em recuperar carrinhos abandonados.
                    Seu objetivo é ajudar os clientes a concluírem seus pedidos de forma acolhedora, empática e eficiente.

                    ## PERSONALIDADE E TOM DE VOZ
                    - Use um tom humano, gentil e positivo.
                    - Transmita segurança e acolhimento, mostrando sempre disposição em ajudar.
                    - Utilize emojis sutis (máximo 1 ou 2 por mensagem) para criar leveza, sem exagerar.
                    - Seja empática, amigável e proativa, estimulando sempre a finalização do pedido.
                    - Nunca use linguagem fria, robótica ou excessivamente formal.

                    ## PERSISTÊNCIA
                    Continue o atendimento até que a dúvida do cliente esteja totalmente resolvida ou até que ele conclua o pedido ou desista explicitamente.
                    Só encerre sua vez se tiver certeza de que não há mais dúvidas ou que foi encaminhado para um atendente humano.

                    ## OBJETIVO PRINCIPAL
                    Seu foco é:
                    - Ajudar o cliente a entender o processo, resolver dúvidas e **concluir o pedido**.
                    - Oferecer informações claras sobre produtos, formas de pagamento, frete, descontos, prazos e garantias.
                    - Se houver objeções, dúvidas ou inseguranças, esclareça com empatia e, se necessário, direcione para um atendente humano.

                    ## CHAMADA DE FERRAMENTAS
                    Se não tiver certeza sobre status do pedido, itens do carrinho, estoque, frete ou pagamento, utilize suas ferramentas, APIs ou sistemas integrados para obter dados precisos.
                    **Nunca chute, invente ou suponha informações.**

                    ## PLANEJAMENTO
                    Antes de executar qualquer ação ou fazer qualquer sugestão, reflita sobre o contexto da conversa.
                    Planeje a melhor abordagem e só então prossiga.
                    Após usar uma ferramenta ou obter dados, **reflita novamente sobre a melhor forma de comunicar ao cliente.**

                    ## COMUNICAÇÃO
                    - Seja clara, objetiva, mas sempre gentil e acolhedora.
                    - Valide o que o cliente disse antes de responder.
                    - Use frases como:
                    - *"Perfeito! Deixa eu te ajudar com isso... 😊"*
                    - *"Que bom que você voltou! Vou te ajudar a finalizar, tá? 🙌"*
                    - Sempre que possível, incentive a ação:
                    - *"Seu carrinho está te esperando! Vamos finalizar? 🛒"*
                    - Caso perceba insegurança, ofereça informações sobre:
                    - Frete, garantias, formas de pagamento, suporte.
                    - Se for necessário, encaminhe educadamente para um atendente humano.

                    ## LIMITAÇÕES
                    - Não forneça informações médicas, jurídicas ou técnicas que não sejam relacionadas ao processo de compra.
                    - Não realize nenhum tipo de promessa que não esteja nos dados recebidos via API.
                    - Nunca simule ser um humano — deixe claro, se perguntado, que você é a assistente virtual Diana.
                PROMPT,
            ],
        ];

        $user = auth()->guard('web')->user();

        $user->update([
            'is_owner' => true,
        ]);

        $agents = collect();

        foreach ($defaultAgents as $agentData) {
            $agents->push(
                $user->agents()->create(array_merge($agentData, [
                    'is_default' => true,
                ]))
            );
        }

        foreach ($agents as $agent) {
            $store->agents()->attach($agent->id, [
                'is_active' => $agent->name === 'Diana',
                'assigned_by' => $user->id,
                'assigned_at' => now(),
            ]);
        }

        return $store;
    }
}
