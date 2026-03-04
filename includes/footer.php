            </main>
        </div>
    </div>
    <script src="<?php echo $base_path ?? ''; ?>js/main.js"></script>
    <script>
    (function() {
        function maskCep(el) {
            var v = (el.value || '').replace(/\D/g, '');
            if (v.length > 5) v = v.slice(0, 5) + '-' + v.slice(5, 8);
            if (el.value !== v) el.value = v;
        }
        function maskCoord(el) {
            var v = (el.value || '').trim();
            var neg = v.charAt(0) === '-' ? '-' : '';
            v = v.replace(/-/g, '').replace(/[^\d.]/g, '');
            var idx = v.indexOf('.');
            if (idx >= 0) {
                var before = v.slice(0, idx);
                var after = v.slice(idx + 1).replace(/\./g, '').slice(0, 8);
                v = before + '.' + after;
            }
            v = neg + v;
            if (el.value !== v) el.value = v;
        }
        function maskPhone(el) {
            var v = (el.value || '').replace(/\D/g, '');
            if (v.length > 11) v = v.slice(0, 11);
            if (v.length <= 2) {
                el.value = v ? '(' + v : '';
            } else if (v.length <= 6) {
                el.value = '(' + v.slice(0, 2) + ') ' + v.slice(2);
            } else if (v.length <= 10) {
                el.value = '(' + v.slice(0, 2) + ') ' + v.slice(2, 6) + '-' + v.slice(6);
            } else {
                el.value = '(' + v.slice(0, 2) + ') ' + v.slice(2, 7) + '-' + v.slice(7);
            }
        }
        function maskCpfCnpj(el) {
            var v = (el.value || '').replace(/\D/g, '');
            if (v.length <= 11) {
                v = v.replace(/(\d{3})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
                v = v.slice(0, 14);
                v = v.replace(/^(\d{2})(\d)/, '$1.$2');
                v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                v = v.replace(/\.(\d{3})(\d{4})/, '.$1/$2');
                v = v.replace(/(\d{4})(\d{2})$/, '$1-$2');
            }
            if (el.value !== v) el.value = v;
        }
        function initMasks() {
            var i, el, cpfCnpj = document.querySelectorAll('.mask-cpf-cnpj, [data-mask="cpf-cnpj"], input[name="cpf_cnpj"]');
            var cep = document.querySelectorAll('.mask-cep, [data-mask="cep"], input[name="CEP"], input[name="cep"]');
            for (i = 0; i < cpfCnpj.length; i++) {
                el = cpfCnpj[i];
                el.setAttribute('maxlength', 18);
                el.setAttribute('inputmode', 'numeric');
                el.addEventListener('input', function() { maskCpfCnpj(this); });
                el.addEventListener('paste', function() { var t=this; setTimeout(function(){ maskCpfCnpj(t); }, 0); });
                el.addEventListener('keyup', function() { maskCpfCnpj(this); });
                if (el.value) maskCpfCnpj(el);
            }
            for (i = 0; i < cep.length; i++) {
                el = cep[i];
                el.setAttribute('maxlength', 9);
                el.setAttribute('inputmode', 'numeric');
                el.addEventListener('input', function() { maskCep(this); });
                el.addEventListener('paste', function() { var t=this; setTimeout(function(){ maskCep(t); }, 0); });
                el.addEventListener('keyup', function() { maskCep(this); });
                if (el.value) maskCep(el);
            }
            var coord = document.querySelectorAll('.mask-coord, [data-mask="coord"], input[name="latitude"], input[name="longitude"]');
            for (i = 0; i < coord.length; i++) {
                el = coord[i];
                el.setAttribute('inputmode', 'decimal');
                el.addEventListener('input', function() { maskCoord(this); });
                el.addEventListener('paste', function() { var t=this; setTimeout(function(){ maskCoord(t); }, 0); });
                el.addEventListener('keyup', function() { maskCoord(this); });
                if (el.value) maskCoord(el);
            }
            var phone = document.querySelectorAll('.mask-phone, [data-mask="phone"], input[name="phone"], input[name="telefone"]');
            for (i = 0; i < phone.length; i++) {
                el = phone[i];
                el.setAttribute('maxlength', 15);
                el.setAttribute('inputmode', 'numeric');
                el.setAttribute('placeholder', '(00) 00000-0000');
                el.addEventListener('input', function() { maskPhone(this); });
                el.addEventListener('paste', function() { var t=this; setTimeout(function(){ maskPhone(t); }, 0); });
                el.addEventListener('keyup', function() { maskPhone(this); });
                if (el.value) maskPhone(el);
            }
        }
        initMasks();
    })();
    </script>
</body>
</html>
