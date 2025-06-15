<?php

declare(strict_types = 1);

namespace App\Filament\Pages\Tenancy;

use App\Enums\IntegrationTypeEnum;
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
                    ->required()
                    ->maxLength(50),

                Select::make('ecommerce')
                    ->label('Plataforma de e-commerce')
                    ->required()
                    ->options(Platform::where('type', IntegrationTypeEnum::ECOMMERCE)->pluck('name', 'id')),

                Select::make('ai')
                    ->label('Inteligência Artificial')
                    ->required()
                    ->options(Platform::where('type', IntegrationTypeEnum::AI)->pluck('name', 'id')),

                Select::make('whatsapp')
                    ->label('Plataforma de comunicação')
                    ->options(Platform::where('type', IntegrationTypeEnum::WHATSAPP)->pluck('name', 'id')),
            ]);
    }

    protected function handleRegistration(array $data): Store
    {
        $store = Store::create([
            'name' => $data['name'],
            'slug' => (string) Str::uuid(),
            'is_active' => true,
            'plan_id' => 1,
        ]);

        $store->integrations()->create([
            'platform_id' => $data['ecommerce'],
            'webhook' => Str::uuid()->toString(),
            'type' => IntegrationTypeEnum::ECOMMERCE,
            'configs' => ['secret' => null],
            'is_active' => true,
        ]);

        $store->integrations()->create([
            'platform_id' => $data['ai'],
            'webhook' => Str::uuid()->toString(),
            'type' => IntegrationTypeEnum::AI,
            'configs' => ['api_key' => null],
            'is_active' => true,
        ]);

        $configPresets = [
            7 => ['phone_number_id' => null, 'waba_id' => null, 'access_token' => null],
            8 => ['instance' => null, 'api_token' => null, 'security_token' => null],
            9 => ['session' => null, 'api_key' => null],
        ];

        $configs = $configPresets[$data['whatsapp']] ?? [];

        $store->integrations()->create([
            'platform_id' => $data['whatsapp'],
            'webhook' => Str::uuid()->toString(),
            'type' => IntegrationTypeEnum::WHATSAPP,
            'configs' => $configs,
            'is_active' => true,
        ]);

        $store->users()->attach(auth()->guard('web')->user());

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
