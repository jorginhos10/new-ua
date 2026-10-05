/**
 * Editor Markdown genérico: popup con barra de formato, editor y vista previa en vivo (basado en
 * el "Mini Editor Markdown con Visor", con soporte de tablas). Requiere vendor/marked.min.js.
 *
 *   EditorMarkdown.abrir({ titulo, contenido, maximo, textoGuardar, alGuardar(texto) })
 *   EditorMarkdown.renderizar(texto)  -> HTML ya saneado (solo texto, listas, tablas, enlaces http/mailto)
 *   Cualquier elemento con data-markdown="..." se muestra renderizado al cargar la página.
 *
 * El texto se escapa antes de convertirlo, así que el HTML escrito a mano nunca llega a la página.
 */
(function (global) {
    'use strict';

    var ETIQUETAS_PERMITIDAS = ['P', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'STRONG', 'EM', 'A', 'UL', 'OL', 'LI',
        'CODE', 'PRE', 'TABLE', 'THEAD', 'TBODY', 'TR', 'TH', 'TD', 'BR', 'HR', 'BLOCKQUOTE', 'DEL'];
    var ETIQUETAS_ELIMINADAS = ['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'NOSCRIPT', 'TEMPLATE'];

    var PLANTILLA_TABLA = '| Encabezado 1 | Encabezado 2 |\n| --- | --- |\n| Dato 1 | Dato 2 |\n| Dato 3 | Dato 4 |';

    function escaparHtml(texto) {
        return String(texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function enlaceSeguro(href) {
        return /^(https?:\/\/|mailto:)/i.test(href);
    }

    // Quita lo que no está permitido: el texto de una etiqueta no permitida se conserva, pero
    // etiquetas como script o style se eliminan con todo su contenido.
    function sanear(nodoPadre) {
        Array.prototype.slice.call(nodoPadre.childNodes).forEach(function (nodo) {
            if (nodo.nodeType === Node.TEXT_NODE) {
                return;
            }

            if (nodo.nodeType !== Node.ELEMENT_NODE || ETIQUETAS_ELIMINADAS.indexOf(nodo.tagName) !== -1) {
                nodo.parentNode.removeChild(nodo);
                return;
            }

            sanear(nodo);

            var href = nodo.tagName === 'A' ? (nodo.getAttribute('href') || '') : null;
            var permitida = ETIQUETAS_PERMITIDAS.indexOf(nodo.tagName) !== -1
                && (nodo.tagName !== 'A' || enlaceSeguro(href));

            if (!permitida) {
                desenvolver(nodo);
                return;
            }

            Array.prototype.slice.call(nodo.attributes).forEach(function (atributo) {
                nodo.removeAttribute(atributo.name);
            });

            if (href !== null) {
                nodo.setAttribute('href', href);
                nodo.setAttribute('target', '_blank');
                nodo.setAttribute('rel', 'noopener noreferrer');
            }
        });
    }

    function desenvolver(nodo) {
        var padre = nodo.parentNode;

        while (nodo.firstChild) {
            padre.insertBefore(nodo.firstChild, nodo);
        }

        padre.removeChild(nodo);
    }

    function renderizar(texto) {
        if (!texto) {
            return '';
        }

        if (!global.marked) {
            return escaparHtml(texto).replace(/\n/g, '<br>');
        }

        var html = global.marked.parse(escaparHtml(texto), { gfm: true, breaks: true, async: false });
        var documento = new DOMParser().parseFromString(html, 'text/html');
        sanear(documento.body);

        return documento.body.innerHTML;
    }

    // Inserta prefijo + selección (o texto por defecto) + sufijo, y deja seleccionado el texto.
    function insertar(area, prefijo, sufijo, porDefecto) {
        var inicio = area.selectionStart;
        var fin = area.selectionEnd;
        var seleccion = area.value.substring(inicio, fin) || porDefecto;

        area.value = area.value.substring(0, inicio) + prefijo + seleccion + sufijo + area.value.substring(fin);
        area.focus();
        area.setSelectionRange(inicio + prefijo.length, inicio + prefijo.length + seleccion.length);
    }

    function insertarTabla(area) {
        var inicio = area.selectionStart;
        var fin = area.selectionEnd;
        var previo = inicio > 0 && area.value.charAt(inicio - 1) !== '\n' ? '\n\n' : '';

        area.value = area.value.substring(0, inicio) + previo + PLANTILLA_TABLA + area.value.substring(fin);
        area.focus();
        area.setSelectionRange(inicio + previo.length, inicio + previo.length + PLANTILLA_TABLA.length);
    }

    var BARRA = [
        { etiqueta: '<b>B</b>', titulo: 'Negrita', accion: function (area) { insertar(area, '**', '**', 'negrita'); } },
        { etiqueta: '<i>I</i>', titulo: 'Cursiva', accion: function (area) { insertar(area, '*', '*', 'cursiva'); } },
        { etiqueta: 'H', titulo: 'Encabezado', accion: function (area) { insertar(area, '# ', '', 'Título'); } },
        { etiqueta: '🔗', titulo: 'Enlace', accion: function (area) { insertar(area, '[', '](https://)', 'enlace'); } },
        { etiqueta: '&lt;/&gt;', titulo: 'Código', accion: function (area) { insertar(area, '`', '`', 'código'); } },
        { etiqueta: '• Lista', titulo: 'Lista', accion: function (area) { insertar(area, '- ', '', 'ítem'); } },
        { etiqueta: '📊', titulo: 'Tabla', accion: insertarTabla }
    ];

    function crearElemento(etiqueta, clase, texto) {
        var elemento = document.createElement(etiqueta);

        if (clase) {
            elemento.className = clase;
        }

        if (texto !== undefined) {
            elemento.textContent = texto;
        }

        return elemento;
    }

    function abrir(opciones) {
        var maximo = opciones.maximo || 0;

        var fondo = crearElemento('div', 'modal-fondo abierto editor-md-fondo');
        var caja = crearElemento('div', 'modal-caja modal-caja-ancha editor-md');
        caja.setAttribute('role', 'dialog');
        caja.setAttribute('aria-modal', 'true');
        caja.appendChild(crearElemento('h2', 'editor-md-titulo', opciones.titulo || 'Editar'));

        var grid = crearElemento('div', 'editor-md-grid');

        var panelEditor = crearElemento('div', 'editor-md-panel');
        panelEditor.appendChild(crearElemento('div', 'editor-md-cabecera', 'EDITOR'));

        var barra = crearElemento('div', 'editor-md-barra');
        var area = crearElemento('textarea', 'editor-md-texto');
        area.placeholder = 'Escribe aquí...';
        area.value = opciones.contenido || '';
        if (maximo) {
            area.maxLength = maximo;
        }

        panelEditor.appendChild(barra);
        panelEditor.appendChild(area);

        var panelVista = crearElemento('div', 'editor-md-panel');
        panelVista.appendChild(crearElemento('div', 'editor-md-cabecera', 'VISTA PREVIA'));
        var vista = crearElemento('div', 'editor-md-vista markdown');
        panelVista.appendChild(vista);

        grid.appendChild(panelEditor);
        grid.appendChild(panelVista);
        caja.appendChild(grid);

        var contador = crearElemento('span', 'editor-md-contador');
        var error = crearElemento('p', 'editor-md-error');
        error.hidden = true;

        var pie = crearElemento('div', 'editor-md-pie');
        var botonCancelar = crearElemento('button', 'boton-accion', 'Cancelar');
        botonCancelar.type = 'button';
        var botonGuardar = crearElemento('button', 'boton-accion boton-accion-enviar', opciones.textoGuardar || 'Guardar');
        botonGuardar.type = 'button';

        pie.appendChild(contador);
        pie.appendChild(botonCancelar);
        pie.appendChild(botonGuardar);
        caja.appendChild(error);
        caja.appendChild(pie);
        fondo.appendChild(caja);

        function actualizar() {
            vista.innerHTML = renderizar(area.value);
            contador.textContent = area.value.length + (maximo ? ' / ' + maximo : '') + ' caracteres';
        }

        BARRA.forEach(function (item) {
            var boton = crearElemento('button', 'editor-md-boton');
            boton.type = 'button';
            boton.title = item.titulo;
            boton.innerHTML = item.etiqueta;
            boton.addEventListener('click', function () {
                item.accion(area);
                actualizar();
            });
            barra.appendChild(boton);
        });

        function cerrar() {
            document.removeEventListener('keydown', alTeclear);
            fondo.parentNode.removeChild(fondo);
        }

        function alTeclear(evento) {
            if (evento.key === 'Escape') {
                cerrar();
            }
        }

        area.addEventListener('input', actualizar);
        botonCancelar.addEventListener('click', cerrar);
        fondo.addEventListener('click', function (evento) {
            if (evento.target === fondo) {
                cerrar();
            }
        });
        document.addEventListener('keydown', alTeclear);

        botonGuardar.addEventListener('click', function () {
            if (maximo && area.value.length > maximo) {
                error.textContent = 'El texto no puede superar los ' + maximo + ' caracteres.';
                error.hidden = false;
                return;
            }

            var texto = area.value;
            cerrar();

            if (opciones.alGuardar) {
                opciones.alGuardar(texto);
            }
        });

        document.body.appendChild(fondo);
        actualizar();
        area.focus();

        return { cerrar: cerrar };
    }

    function inicializarVistas(raiz) {
        (raiz || document).querySelectorAll('[data-markdown]').forEach(function (elemento) {
            elemento.innerHTML = renderizar(elemento.getAttribute('data-markdown'));
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        inicializarVistas(document);
    });

    global.EditorMarkdown = {
        abrir: abrir,
        renderizar: renderizar,
        inicializarVistas: inicializarVistas
    };
})(window);
