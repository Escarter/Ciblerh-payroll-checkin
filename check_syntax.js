const fs = require('fs');
const content = fs.readFileSync('resources/views/livewire/portal/dashboard/index.blade.php', 'utf8');

// Extract JavaScript content between <script> tags
const jsMatch = content.match(/<script[^>]*>([\s\S]*?)<\/script>/g);
if (jsMatch) {
  const jsCode = jsMatch[0].replace(/<script[^>]*>/, '').replace(/<\/script>/, '');
  console.log('JavaScript code length:', jsCode.length);
  try {
    new Function(jsCode);
    console.log('JavaScript syntax is valid');
  } catch (e) {
    console.log('JavaScript syntax error:', e.message);
    console.log('Error position details:', e);
  }
} else {
  console.log('No script tags found');
}