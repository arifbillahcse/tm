<?php
// Operator-only configuration. Never accept an endpoint from a browser request.
return ['enabled'=>env('OLLAMA_ENABLED',false),'model'=>env('OLLAMA_MODEL','qwen2.5:3b')];
