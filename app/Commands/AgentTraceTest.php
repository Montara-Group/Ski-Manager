<?php

namespace App\Commands;

use App\Libraries\AgentTracing;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AgentTraceTest extends BaseCommand
{
    protected $group       = 'Observability';
    protected $name        = 'agent:test';
    protected $description = 'Sends a test Sentry Agent Tracing & Conversation interaction to verify ingestion.';

    public function run(array $params)
    {
        CLI::write('Emitting Sentry Agent Tracing & Conversation interaction...', 'yellow');

        $conversationId = 'conv_' . bin2hex(random_bytes(8));
        $agentName      = 'SkiManagerAdvisoryAgent';

        $tx = AgentTracing::startAgentTransaction(
            $conversationId,
            $agentName,
            'Live Agent Advisory Simulation'
        );

        if (! $tx) {
            CLI::error('Sentry is not initialized or SENTRY_DSN is not configured.');
            return;
        }

        CLI::write("Started Agent Transaction (Conversation ID: {$conversationId})", 'green');

        // Chat Turn 1
        $userPrompt     = 'What is the current slope grooming status and expected visitor count today?';
        $assistantReply = 'All 14 upper mountain trails have been groomed with fresh corduroy. Weather model predicts bluebird skies and 850 estimated visitors.';

        $chatSpan = AgentTracing::recordChatTurn(
            $tx,
            $conversationId,
            $agentName,
            $userPrompt,
            $assistantReply,
            [
                'provider'      => 'google',
                'model'         => 'gemini-1.5-pro',
                'input_tokens'  => 18,
                'output_tokens' => 28,
            ]
        );

        // Tool execution inside chat turn
        $toolInput  = ['resort_id' => 1, 'date' => date('Y-m-d')];
        $toolOutput = ['groomed_trails' => 14, 'open_lifts' => 5, 'temp_c' => -2];
        $toolSpan   = AgentTracing::recordToolExecution(
            $chatSpan,
            $conversationId,
            'get_resort_conditions',
            $toolInput,
            $toolOutput
        );
        usleep(15000); // 15ms simulated tool latency
        $toolSpan->finish();

        $chatSpan->finish();

        AgentTracing::finishAgentTransaction($tx);

        CLI::write('Successfully recorded and flushed Agent Tracing conversation turn to Sentry!', 'green');
    }
}
