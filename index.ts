import { streamText } from 'ai';
import { createOpenAI } from '@ai-sdk/openai';
import dotenv from 'dotenv';

// Load environment variables from .env.local
dotenv.config({ path: '.env.local' });

const apiKey = process.env.AI_GATEWAY_API_KEY;

if (!apiKey || apiKey === 'your_ai_gateway_api_key_here') {
  console.error('⚠️ Please set a valid AI_GATEWAY_API_KEY in .env.local before running!');
  process.exit(1);
}

// Configure Vercel AI Gateway provider
const openai = createOpenAI({
  baseURL: 'https://ai-gateway.vercel.sh/v1',
  apiKey: apiKey,
});

async function main() {
  console.log('Streaming response from openai/gpt-5.4 via Vercel AI Gateway...\n');

  try {
    const result = streamText({
      model: openai('openai/gpt-5.4'),
      prompt: 'Explain how Vercel AI Gateway simplifies LLM routing in 3 concise bullet points.',
    });

    for await (const delta of result.textStream) {
      process.stdout.write(delta);
    }

    console.log('\n');

    const usage = await result.usage;
    console.log('--- Token Usage ---');
    console.log(`Prompt Tokens:     ${usage.promptTokens}`);
    console.log(`Completion Tokens: ${usage.completionTokens}`);
    console.log(`Total Tokens:      ${usage.totalTokens}`);
  } catch (error) {
    console.error('Execution Error:', error);
  }
}

main();
