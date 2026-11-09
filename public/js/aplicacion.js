// JavaScript nativo del Sistema de Gestión de Biblioteca Escolar
(function () {
    'use strict';

    // ---------- Confirmación antes de acciones delicadas ----------
    // Uso: <form method="post" data-confirmar="¿Seguro?">
    var modalEl = document.getElementById('modalConfirmar');
    if (modalEl && window.bootstrap) {
        var modal = new bootstrap.Modal(modalEl);
        var formularioPendiente = null;
        document.addEventListener('submit', function (evento) {
            var form = evento.target;
            if (!(form instanceof HTMLFormElement) || !form.dataset.confirmar || form.dataset.confirmado === '1') {
                return;
            }
            evento.preventDefault();
            formularioPendiente = form;
            document.getElementById('modalConfirmarTexto').textContent = form.dataset.confirmar;
            modal.show();
        });
        document.getElementById('modalConfirmarAceptar').addEventListener('click', function () {
            if (formularioPendiente) {
                formularioPendiente.dataset.confirmado = '1';
                formularioPendiente.submit();
            }
        });
    }

    // ---------- Evitar doble envío ----------
    document.addEventListener('submit', function (evento) {
        var form = evento.target;
        if (evento.defaultPrevented || !(form instanceof HTMLFormElement)) {
            return;
        }
        window.setTimeout(function () {
            form.querySelectorAll('button[type="submit"]').forEach(function (b) { b.disabled = true; });
        }, 0);
    });

    // Al volver con el botón "atrás" los botones deben quedar habilitados
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            document.querySelectorAll('button[type="submit"]').forEach(function (b) { b.disabled = false; });
        }
    });

    // ---------- Autocompletar (lectores y ejemplares) ----------
    // <input class="autocompletar" data-url="/prestamos/buscar.php?tipo=lector" data-destino="#usuario_id">
    document.querySelectorAll('input.autocompletar').forEach(function (campo) {
        var destino = document.querySelector(campo.dataset.destino);
        var caja = campo.parentElement;
        var lista = document.createElement('div');
        lista.className = 'autocompletar-lista';
        lista.hidden = true;
        caja.appendChild(lista);
        var temporizador = null;

        function cerrar() { lista.hidden = true; lista.innerHTML = ''; }

        function elegir(item) {
            destino.value = item.id;
            campo.value = item.texto;
            campo.setCustomValidity('');
            cerrar();
        }

        function mostrar(items) {
            lista.innerHTML = '';
            if (!items.length) {
                var aviso = document.createElement('div');
                aviso.className = 'autocompletar-aviso';
                aviso.textContent = 'Sin resultados.';
                lista.appendChild(aviso);
            }
            items.forEach(function (item) {
                var b = document.createElement('button');
                b.type = 'button';
                var t = document.createElement('span');
                t.textContent = item.texto;
                var d = document.createElement('small');
                d.textContent = item.detalle || '';
                b.appendChild(t);
                b.appendChild(d);
                b.addEventListener('click', function () { elegir(item); });
                lista.appendChild(b);
            });
            lista.hidden = false;
        }

        campo.addEventListener('input', function () {
            destino.value = '';
            campo.setCustomValidity('Seleccione una opción de la lista.');
            window.clearTimeout(temporizador);
            var q = campo.value.trim();
            if (q.length < 1) { cerrar(); return; }
            temporizador = window.setTimeout(function () {
                fetch(campo.dataset.url + '&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
                    .then(function (r) { return r.ok ? r.json() : []; })
                    .then(mostrar)
                    .catch(cerrar);
            }, 250);
        });
        campo.addEventListener('focus', function () {
            if (!destino.value) { campo.dispatchEvent(new Event('input')); }
        });
        document.addEventListener('click', function (e) { if (!caja.contains(e.target)) { cerrar(); } });
        if (!destino.value) { campo.setCustomValidity('Seleccione una opción de la lista.'); }
    });

    // ---------- Fecha límite sugerida ----------
    var prestamo = document.getElementById('fecha_prestamo');
    var limite = document.getElementById('fecha_limite');
    if (prestamo && limite) {
        prestamo.addEventListener('change', function () {
            if (!prestamo.value) { return; }
            var d = new Date(prestamo.value + 'T00:00:00');
            d.setDate(d.getDate() + parseInt(limite.dataset.dias || '7', 10));
            var mes = String(d.getMonth() + 1).padStart(2, '0');
            var dia = String(d.getDate()).padStart(2, '0');
            limite.value = d.getFullYear() + '-' + mes + '-' + dia;
            limite.min = prestamo.value;
        });
    }
})();
