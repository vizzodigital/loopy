notificar email:
bem vindo
resumo de acesso.(link de acesso)

# Fluxo (racional)

## Visão Geral do Fluxo

### Etapa 1: Recebimento do Carrinho Abandonado

Dados do cliente e do carrinho são persistidos:
. Customer
. AbandonedCart (relacionado ao Store)
O sistema detecta que há um novo carrinho abandonado e dispara um prompt IA inicial.

### Etapa 2: Criação da Conversa e Primeira Mensagem

Criamos uma instância de Conversation:
. Relacionada ao AbandonedCart
. Pode ter status (ativa, encerrada, assumida por humano, etc.)
Criamos o primeiro ConversationMessage:
. sender_type: AI
. content: gerado via prompt com contexto do carrinho
. Armazenado para consulta futura da IA (e auditoria)

### Etapa 3: Resposta do Cliente (Webhook do WhatsApp)

Mensagem chega pela rota /webhook/zapi/...
Identificamos o cliente com base no telefone (armazenado em Customer)
Verificamos se ele está vinculado a um AbandonedCart com Conversation ativa.
Criamos nova ConversationMessage:
. sender_type: Customer
. content: texto da mensagem recebida
A IA processa essa resposta e responde (se não for humano o último remetente).

### Etapa 4: Continuação ou Encerramento da Conversa

AI responde, investigando o abandonment_reason com mais mensagens.
Quando um motivo fica evidente, associamos ao AbandonedCart.
Se a venda for recuperada, atualizamos:
. status do AbandonedCart para RECOVERED
. total_amount_recovered
Se um atendente humano entra, alteramos o status da conversa e paramos as mensagens da IA.

### Lógica de Contexto

`$context = $conversation->messages()->latest()->take(10)->get();`

# Resumo

Models
User: name, email, password, is_active, is_owner
Relationship: Store::class BelongsToMany, Agent::class HasMany
Pivot: store_user

Platform: name, type, is_active
Enum: IntegrationTypeEnum: ecommerce, ai, whatsapp, social
Relationship: Store::class HasMany, Integration::class HasMany

Plan: name, slug, price, description, features, is_active
Relationship: Store::class HasMany

Store: name, slug, is_active, plan_id
Relationship: User::class BelongsToMany, Plan::class BelongsTo, Customer::class HasMany, Integration::class HasMany, Agent::class BelongsToMany
Pivot: store_user, agent_store

Integration: store_id, webhook, type, configs, is_active
Enum: IntegrationTypeEnum: ecommerce, ai, whatsapp, social
Relationship: Store::class BelongsTo

Agent: user_id, name, description, model, temperature, top_p, frequency_penalty, presence_penalty, max_tokens, system_prompt, is_active, is_test, is_default
Relationship: User::class BelongsTo, Store::class BelongsToMany
Pivot: agent_store

Customer: store_id, external_id, name, email, phone, whatsapp
Relationship: Store::class BelongsTo, AbandonedCart::class HasMany

AbandonedCart: store_id, customer_id, external_cart_id, cart_data, customer_data, total_amount, total_amount_recovered, status, abandonment_reason_id
Relationship: Store::class BelongsTo, Customer::class BelongsTo, AbandonmentReason::class BelongsTo, Conversation::class HasOne
Enum: CartStatusEnum: abandoned, recovered, lost

AbandonmentReason: name, description, is_active
Relationship: AbandonedCart::class HasMany

Conversation: abandoned_cart_id, status, started_at, closed_at, human_assumed_at, human_user_id
Relationship: AbandonedCart::class BelongsTo, ConversationMessage::class HasMany, User::class BelongsTo (human_user_id)
Enum: ConversationStatusEnum: open, closed, pending, human

ConversationMessage: conversation_id, sender_type, content, payload, was_read, sent_at
Relationship: Conversation::class BelongsTo
Enum: ConversationSenderTypeEnum: customer, ia, human

Routes: api.php
Route::post('/webhook/store/{webhook}', WebhookStoreController::class); //invokable: Recebe das plataformas de e-commerce.

Route::post('/webhook/whatsapp/official/{webhook}', WebhookWhatsappOfficialController::class); //invokable: Recebe da api do whatsapp business

Route::post('/webhook/whatsapp/zapi/{webhook}', WebhookWhatsappZapiController::class); //invokable: Recebe da api Z-Api: whatsapp não oficial

Route::post('/webhook/whatsapp/waha/{webhook}', WebhookWhatsappWahaController::class); //invokable: Recebe da api Waha: whatsapp não oficial

Routes: web.php
Route::post('/agents/install-defaults', InstallDefaultAgentsController::class); //invokable: instala os agentes defaults do sistema (modelos).
