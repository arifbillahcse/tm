@if(session('status'))<div class="message" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="message error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
