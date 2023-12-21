@if(config('marketplace.js_warning'))
    <div class="mt-3">
        <div id="jswarning"></div>
    </div>
    <script>
        let warningText = 'You have JavaScript enabled, you are putting yourself at risk! Please disable it immediately!'
        let jsWarning = document.getElementById('jswarning');
        let alert = document.createElement('div');
        let span = document.createElement('span');
        alert.classList.add('alert');
        alert.classList.add('alert-danger');
        span.innerText = warningText;
        alert.appendChild(span);
        jsWarning.appendChild(alert);
    </script>
@endif
