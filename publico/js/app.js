/**
 * Establece el valor de un campo que puede ser un <select>/<input> normal o (más de 10 opciones)
 * un selector-buscable (input visible + input oculto con el mismo id base + "_buscador"). Usarlo
 * al abrir un modal de edición evita tener que saber, en cada punto de llamada, si ese campo ya
 * fue convertido a selector-buscable o sigue siendo un <select> plano.
 */
function establecerValorBuscable(idBase, valor) {
    var valorFinal = valor !== null && valor !== undefined ? valor : '';
    var campoTexto = document.getElementById(idBase + '_buscador');
    var campoOculto = document.getElementById(idBase);

    if (campoTexto && campoOculto) {
        var opcion = document.querySelector('#' + idBase + '_lista .selector-buscable-opcion[data-id="' + CSS.escape(String(valorFinal)) + '"]');
        campoTexto.value = valorFinal !== '' ? (opcion ? (opcion.dataset.mostrar || opcion.dataset.texto) : valorFinal) : '';
        campoOculto.value = valorFinal;
        campoOculto.dispatchEvent(new Event('change'));

        return;
    }

    var elemento = document.getElementById(idBase);

    if (elemento) {
        elemento.value = valorFinal;
    }
}

window.establecerValorBuscable = establecerValorBuscable;

document.addEventListener('DOMContentLoaded', function () {
    var botonesModoVista = document.querySelectorAll('[data-modo-vista]');

    botonesModoVista.forEach(function (boton) {
        boton.addEventListener('click', function () {
            var modo = boton.dataset.modoVista;

            botonesModoVista.forEach(function (otroBoton) {
                otroBoton.classList.toggle('activo', otroBoton === boton);
            });

            document.querySelectorAll('[data-vista-jerarquia]').forEach(function (seccion) {
                seccion.hidden = seccion.dataset.vistaJerarquia !== modo;
            });
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-reloj-arena-anillo]').forEach(function (anillo) {
        var offsetFinal = anillo.dataset.offsetFinal;

        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () {
                anillo.style.strokeDashoffset = offsetFinal;
            });
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-mini-slider]').forEach(function (miniSlider) {
        var diapositivas = Array.prototype.slice.call(miniSlider.querySelectorAll('[data-mini-slider-slide]'));
        var puntos = Array.prototype.slice.call(miniSlider.querySelectorAll('.mini-slider-punto'));
        var titulo = miniSlider.querySelector('[data-mini-slider-titulo]');
        var botonPrev = miniSlider.querySelector('[data-mini-slider-prev]');
        var botonNext = miniSlider.querySelector('[data-mini-slider-next]');
        var indice = 0;

        function mostrar(nuevoIndice) {
            indice = (nuevoIndice + diapositivas.length) % diapositivas.length;

            diapositivas.forEach(function (diapositiva, posicion) {
                diapositiva.hidden = posicion !== indice;
            });

            puntos.forEach(function (punto, posicion) {
                punto.classList.toggle('activo', posicion === indice);
            });

            if (titulo && diapositivas[indice]) {
                titulo.textContent = diapositivas[indice].dataset.titulo || titulo.textContent;
            }
        }

        if (botonPrev) {
            botonPrev.addEventListener('click', function () {
                mostrar(indice - 1);
            });
        }

        if (botonNext) {
            botonNext.addEventListener('click', function () {
                mostrar(indice + 1);
            });
        }

        puntos.forEach(function (punto, posicion) {
            punto.addEventListener('click', function () {
                mostrar(posicion);
            });
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var camposPorcentaje = document.querySelectorAll('.campo-porcentaje-valor');

    camposPorcentaje.forEach(function (campo) {
        var checkbox = document.querySelector('input[name="' + campo.dataset.noAplica + '"]');

        if (!checkbox) {
            return;
        }

        checkbox.addEventListener('change', function () {
            campo.disabled = checkbox.checked;

            if (checkbox.checked) {
                campo.dataset.valorPrevio = campo.value;
                campo.value = '';
                campo.placeholder = 'No aplica';
            } else {
                campo.placeholder = '0.00';

                if (campo.dataset.valorPrevio) {
                    campo.value = campo.dataset.valorPrevio;
                }
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var botonHamburguesa = document.getElementById('boton-hamburguesa');
    var barraLateral = document.querySelector('.barra-lateral');

    if (!botonHamburguesa || !barraLateral) {
        return;
    }

    if (localStorage.getItem('sidebarColapsado') !== '0') {
        barraLateral.classList.add('colapsada');
    }

    botonHamburguesa.addEventListener('click', function () {
        barraLateral.classList.toggle('colapsada');
        localStorage.setItem('sidebarColapsado', barraLateral.classList.contains('colapsada') ? '1' : '0');
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var boton = document.getElementById('menu-usuario-boton');
    var dropdown = document.getElementById('menu-usuario-dropdown');

    if (!boton || !dropdown) {
        return;
    }

    boton.addEventListener('click', function (evento) {
        evento.stopPropagation();
        dropdown.classList.toggle('abierto');
    });

    document.addEventListener('click', function (evento) {
        if (!dropdown.contains(evento.target) && !boton.contains(evento.target)) {
            dropdown.classList.remove('abierto');
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var boton = document.getElementById('menu-mensajes-boton');
    var dropdown = document.getElementById('menu-mensajes-dropdown');

    if (!boton || !dropdown) {
        return;
    }

    boton.addEventListener('click', function (evento) {
        evento.stopPropagation();
        dropdown.classList.toggle('abierto');
    });

    document.addEventListener('click', function (evento) {
        if (!dropdown.contains(evento.target) && !boton.contains(evento.target)) {
            dropdown.classList.remove('abierto');
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var campoProyectoId = document.getElementById('proyecto_id');
    var campoProyectoTexto = document.getElementById('proyecto_buscador');
    var campoObjetoProyecto = document.getElementById('objeto_proyecto_paa');
    var campoActividad = document.getElementById('actividad');
    var formularioGasto = campoProyectoId ? campoProyectoId.closest('form') : null;
    var rutasConRecordado = ['gastos', 'extension', 'sin-excedentes', 'unisalud', 'postgrado'];
    var ruta = new URLSearchParams(window.location.search).get('ruta') || '';

    if (formularioGasto && rutasConRecordado.indexOf(ruta) !== -1) {
        var claveRecordado = 'ua_campos_recordados_' + ruta;

        try {
            var recordado = JSON.parse(localStorage.getItem(claveRecordado) || 'null');

            if (recordado) {
                if (recordado.proyecto_id) {
                    campoProyectoId.value = recordado.proyecto_id;

                    if (campoProyectoTexto && recordado.proyecto_texto) {
                        campoProyectoTexto.value = recordado.proyecto_texto;
                    }
                }

                if (campoObjetoProyecto && recordado.objeto_proyecto_paa) {
                    campoObjetoProyecto.value = recordado.objeto_proyecto_paa;
                }

                if (campoActividad && recordado.actividad) {
                    campoActividad.value = recordado.actividad;
                }
            }
        } catch (error) {
            // localStorage no disponible o valor guardado inválido: se ignora.
        }

        formularioGasto.addEventListener('submit', function () {
            try {
                localStorage.setItem(claveRecordado, JSON.stringify({
                    proyecto_id: campoProyectoId.value,
                    proyecto_texto: campoProyectoTexto ? campoProyectoTexto.value : '',
                    objeto_proyecto_paa: campoObjetoProyecto ? campoObjetoProyecto.value : '',
                    actividad: campoActividad ? campoActividad.value : '',
                }));
            } catch (error) {
                // Almacenamiento no disponible: no bloquea el envío del formulario.
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('modal-necesidad');
    var botonCerrar = document.getElementById('boton-cerrar-modal-necesidad');
    var botonImprimir = document.getElementById('boton-imprimir-necesidad');
    var filas = document.querySelectorAll('.fila-clickeable');

    if (!modal || filas.length === 0) {
        return;
    }

    var campos = {
        solicitante: document.getElementById('doc-solicitante'),
        responsable: document.getElementById('doc-responsable'),
        sede: document.getElementById('doc-sede'),
        dependencia: document.getElementById('doc-dependencia'),
        programa: document.getElementById('doc-programa'),
        linea: document.getElementById('doc-linea'),
        sublinea: document.getElementById('doc-sublinea'),
        pdi: document.getElementById('doc-pdi'),
        detalle: document.getElementById('doc-detalle'),
        articulacion: document.getElementById('doc-articulacion'),
        espacio: document.getElementById('doc-espacio'),
        requisitos: document.getElementById('doc-requisitos'),
        valor: document.getElementById('doc-valor'),
        fuente: document.getElementById('doc-fuente'),
        observaciones: document.getElementById('doc-observaciones'),
        fecha: document.getElementById('doc-fecha'),
        vigencia: document.getElementById('doc-vigencia'),
        nombreNecesidad: document.getElementById('doc-nombre-necesidad'),
        descripcion: document.getElementById('doc-descripcion'),
        justificacion: document.getElementById('doc-justificacion'),
        estamento: document.getElementById('doc-estamento'),
        beneficiarios: document.getElementById('doc-beneficiarios'),
        beneficiariosEstamentos: document.getElementById('doc-beneficiarios-estamentos'),
    };

    function textoOGuion(valor) {
        return valor ? valor : '—';
    }

    function formatoValor(valor) {
        var numero = parseFloat(valor);

        if (isNaN(numero)) {
            return textoOGuion(valor);
        }

        return numero.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function nombresBeneficiarios(lista) {
        if (!lista || lista.length === 0) {
            return '';
        }

        return lista.map(function (estamento) {
            return estamento.nombre;
        }).join(', ');
    }

    function abrirModalConNecesidad(necesidad) {
        campos.solicitante.textContent = textoOGuion(necesidad.nombre_solicitante);
        campos.responsable.textContent = textoOGuion(necesidad.responsable_nombre);
        campos.sede.textContent = textoOGuion(necesidad.sede_nombre);
        campos.dependencia.textContent = textoOGuion(necesidad.dependencia);
        campos.programa.textContent = textoOGuion(necesidad.programa_academico);
        campos.linea.textContent = textoOGuion(necesidad.linea_inversion_nombre || necesidad.linea_inversion);
        campos.sublinea.textContent = textoOGuion(necesidad.sublinea_inversion_nombre || necesidad.sublinea_inversion);
        campos.pdi.textContent = textoOGuion(necesidad.proyecto_nombre);
        campos.detalle.textContent = textoOGuion(necesidad.detalle_inversion);
        campos.articulacion.textContent = textoOGuion(necesidad.articulacion_plan);
        campos.espacio.textContent = textoOGuion(necesidad.espacio_intervenir);
        campos.requisitos.textContent = textoOGuion(necesidad.requisitos_normativos);
        campos.valor.textContent = formatoValor(necesidad.valor);
        campos.fuente.textContent = textoOGuion(necesidad.fuente_financiacion);
        campos.observaciones.textContent = textoOGuion(necesidad.observaciones);
        campos.fecha.textContent = textoOGuion(necesidad.creado_en);
        campos.vigencia.textContent = textoOGuion(necesidad.vigencia);
        campos.nombreNecesidad.textContent = textoOGuion(necesidad.nombre_necesidad);
        campos.descripcion.textContent = textoOGuion(necesidad.descripcion);
        campos.justificacion.textContent = textoOGuion(necesidad.justificacion);
        campos.estamento.textContent = textoOGuion(necesidad.estamento_solicitante_nombre);
        campos.beneficiarios.textContent = textoOGuion(necesidad.beneficiarios_cantidad);
        campos.beneficiariosEstamentos.textContent = textoOGuion(nombresBeneficiarios(necesidad.beneficiarios_estamentos));

        modal.classList.add('abierto');
    }

    function cerrarModalNecesidad() {
        modal.classList.remove('abierto');
    }

    filas.forEach(function (fila) {
        fila.addEventListener('click', function () {
            try {
                var necesidad = JSON.parse(fila.dataset.necesidad);
                abrirModalConNecesidad(necesidad);
            } catch (error) {
                return;
            }
        });

        fila.addEventListener('keydown', function (evento) {
            if (evento.key === 'Enter' || evento.key === ' ') {
                evento.preventDefault();
                fila.click();
            }
        });
    });

    if (botonCerrar) {
        botonCerrar.addEventListener('click', cerrarModalNecesidad);
    }

    if (botonImprimir) {
        botonImprimir.addEventListener('click', function () {
            window.print();
        });
    }

    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) {
            cerrarModalNecesidad();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarModalNecesidad();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var formularioEditarProyecto = document.getElementById('form-editar-proyecto');

    if (!formularioEditarProyecto) {
        return;
    }

    document.querySelectorAll('.boton-editar-proyecto').forEach(function (boton) {
        boton.addEventListener('click', function (evento) {
            evento.stopPropagation();

            var necesidad;

            try {
                necesidad = JSON.parse(boton.dataset.proyecto);
            } catch (error) {
                return;
            }

            document.getElementById('editar-proyecto-id').value = necesidad.id;
            document.getElementById('editar-proyecto-volver').value = necesidad.volver || '';
            document.getElementById('editar-proyecto-vigencia').value = necesidad.vigencia || '';
            document.getElementById('editar-proyecto-nombre_necesidad').value = necesidad.nombre_necesidad || '';
            document.getElementById('editar-proyecto-descripcion').value = necesidad.descripcion || '';
            document.getElementById('editar-proyecto-justificacion').value = necesidad.justificacion || '';
            document.getElementById('editar-proyecto-estamento_solicitante_id').value = necesidad.estamento_solicitante_id || '';
            document.getElementById('editar-proyecto-beneficiarios_cantidad').value = necesidad.beneficiarios_cantidad || '';

            var idsBeneficiarios = Array.isArray(necesidad.beneficiarios_estamentos)
                ? necesidad.beneficiarios_estamentos.map(function (estamento) { return String(estamento.id); })
                : [];
            document.querySelectorAll('#editar-proyecto-beneficiarios-estamentos-grupo .tag-chip').forEach(function (casilla) {
                casilla.checked = idsBeneficiarios.indexOf(casilla.value) !== -1;
            });

            establecerValorBuscable('editar-proyecto-linea_inversion', necesidad.linea_inversion);
            establecerValorBuscable('editar-proyecto-sublinea_inversion', necesidad.sublinea_inversion);
            document.getElementById('editar-proyecto-detalle_inversion').value = necesidad.detalle_inversion || '';
            document.getElementById('editar-proyecto-sede_id').value = necesidad.sede_id || '';
            establecerValorBuscable('editar-proyecto-dependencia', necesidad.dependencia);
            establecerValorBuscable('editar-proyecto-programa-academico-dependencia', necesidad.programa_academico);

            var campoProyectoTexto = document.getElementById('editar-proyecto-proyecto_buscador');
            var campoProyectoId = document.getElementById('editar-proyecto-proyecto_id');

            if (necesidad.proyecto_pdi_id) {
                var opcionProyecto = document.querySelector('#editar-proyecto-proyecto_lista .selector-buscable-opcion[data-id="' + necesidad.proyecto_pdi_id + '"]');
                campoProyectoTexto.value = opcionProyecto ? (opcionProyecto.dataset.mostrar || opcionProyecto.dataset.texto) : '';
                campoProyectoId.value = necesidad.proyecto_pdi_id;
            } else {
                campoProyectoTexto.value = '';
                campoProyectoId.value = '';
            }

            document.getElementById('editar-proyecto-articulacion_plan').value = necesidad.articulacion_plan || '';
            document.getElementById('editar-proyecto-espacio_intervenir').value = necesidad.espacio_intervenir || '';
            document.getElementById('editar-proyecto-requisitos_normativos').value = necesidad.requisitos_normativos || '';
            document.getElementById('editar-proyecto-valor').value = necesidad.valor || '';
            document.getElementById('editar-proyecto-fuente_financiacion').value = necesidad.fuente_financiacion || '';
            document.getElementById('editar-proyecto-responsable_usuario_id').value = necesidad.responsable_usuario_id || '';
            document.getElementById('editar-proyecto-observaciones').value = necesidad.observaciones || '';
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var formularioEdicion = document.getElementById('form-editar-proyecto');

    if (!formularioEdicion) {
        return;
    }

    var formularioSucio = false;

    setTimeout(function () {
        formularioEdicion.addEventListener('input', function () { formularioSucio = true; });
        formularioEdicion.addEventListener('change', function () { formularioSucio = true; });
    }, 0);

    var modalConfirmar = document.getElementById('modal-confirmar-salir-edicion');
    var botonSeguirEditando = document.getElementById('boton-seguir-editando-edicion');
    var botonSalirSinGuardar = document.getElementById('boton-salir-sin-guardar-edicion');
    var accionPendiente = null;

    function abrirConfirmacion(callback) {
        if (!formularioSucio) {
            callback();
            return;
        }

        accionPendiente = callback;

        if (modalConfirmar) {
            modalConfirmar.classList.add('abierto');
        } else {
            callback();
        }
    }

    function cerrarConfirmacion() {
        accionPendiente = null;

        if (modalConfirmar) {
            modalConfirmar.classList.remove('abierto');
        }
    }

    if (botonSeguirEditando) {
        botonSeguirEditando.addEventListener('click', cerrarConfirmacion);
    }

    if (botonSalirSinGuardar) {
        botonSalirSinGuardar.addEventListener('click', function () {
            var callback = accionPendiente;
            formularioSucio = false;
            cerrarConfirmacion();

            if (callback) {
                callback();
            }
        });
    }

    var enlaceVolver = document.querySelector('.barra-modulo-volver');

    if (enlaceVolver) {
        enlaceVolver.addEventListener('click', function (evento) {
            if (formularioSucio) {
                evento.preventDefault();
                abrirConfirmacion(function () {
                    window.location.href = enlaceVolver.href;
                });
            }
        });
    }

    var botonNuevoItem = document.getElementById('boton-nuevo-item-desde-edicion');
    var modalProyecto = document.getElementById('modal-crear-proyecto');

    if (botonNuevoItem && modalProyecto) {
        botonNuevoItem.addEventListener('click', function () {
            abrirConfirmacion(function () {
                modalProyecto.classList.add('abierto');
            });
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('modal-gasto');
    var botonAbrir = document.getElementById('boton-abrir-modal-gasto');
    var botonCerrar = document.getElementById('boton-cerrar-modal-gasto');

    if (!modal) {
        return;
    }

    function abrirModal() {
        modal.classList.add('abierto');
    }

    function cerrarModal() {
        modal.classList.remove('abierto');
    }

    if (botonAbrir) {
        botonAbrir.addEventListener('click', abrirModal);
    }

    if (botonCerrar) {
        botonCerrar.addEventListener('click', cerrarModal);
    }

    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) {
            cerrarModal();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarModal();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('modal-crear-proyecto');
    var botonAbrir = document.getElementById('boton-abrir-modal-crear-proyecto');
    var botonCerrar = document.getElementById('boton-cerrar-modal-crear-proyecto');

    if (!modal) {
        return;
    }

    function abrirModal() {
        modal.classList.add('abierto');
    }

    function cerrarModal() {
        modal.classList.remove('abierto');
    }

    if (botonAbrir) {
        botonAbrir.addEventListener('click', abrirModal);
    }

    if (botonCerrar) {
        botonCerrar.addEventListener('click', cerrarModal);
    }

    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) {
            cerrarModal();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarModal();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('modal-enviar-todo-gasto');
    var botonAbrir = document.getElementById('boton-abrir-modal-enviar-todo-gasto');
    var botonCerrar = document.getElementById('boton-cerrar-modal-enviar-todo-gasto');

    if (!modal || !botonAbrir) {
        return;
    }

    function abrirModal() {
        modal.classList.add('abierto');
    }

    function cerrarModal() {
        modal.classList.remove('abierto');
    }

    botonAbrir.addEventListener('click', abrirModal);

    if (botonCerrar) {
        botonCerrar.addEventListener('click', cerrarModal);
    }

    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) {
            cerrarModal();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarModal();
        }
    });
});

['extension', 'postgrado', 'sin-excedentes', 'unisalud', 'perfil-proyectos'].forEach(function (moduloAutogestion) {
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('modal-enviar-todo-' + moduloAutogestion);
        var botonAbrir = document.getElementById('boton-abrir-modal-enviar-todo-' + moduloAutogestion);
        var botonCerrar = document.getElementById('boton-cerrar-modal-enviar-todo-' + moduloAutogestion);

        if (!modal || !botonAbrir) {
            return;
        }

        function abrirModal() {
            modal.classList.add('abierto');
        }

        function cerrarModal() {
            modal.classList.remove('abierto');
        }

        botonAbrir.addEventListener('click', abrirModal);

        if (botonCerrar) {
            botonCerrar.addEventListener('click', cerrarModal);
        }

        modal.addEventListener('click', function (evento) {
            if (evento.target === modal) {
                cerrarModal();
            }
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape') {
                cerrarModal();
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.form-eliminar-gasto').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            if (!confirm('¿Eliminar este gasto? Esta acción no se puede deshacer.')) {
                evento.preventDefault();
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var formularioEditarGasto = document.getElementById('form-editar-gasto');

    if (!formularioEditarGasto) {
        return;
    }

    document.querySelectorAll('.boton-editar-gasto').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var gasto;

            try {
                gasto = JSON.parse(boton.dataset.gasto);
            } catch (error) {
                return;
            }

            document.getElementById('editar-gasto-id').value = gasto.id;
            document.getElementById('editar-gasto-volver').value = gasto.volver || '';
            document.getElementById('editar-anio_presupuestal_id').value = gasto.anio_presupuestal_id;
            document.getElementById('editar-sede_id').value = gasto.sede_id;
            establecerValorBuscable('editar-dependencia', gasto.dependencia);
            document.getElementById('editar-actividad').value = gasto.actividad;
            document.getElementById('editar-insumo').value = gasto.insumo;
            document.getElementById('editar-cantidad').value = gasto.cantidad;
            document.getElementById('editar-costo_unitario').value = gasto.costo_unitario;

            var campoContratoTexto = document.getElementById('editar-objeto_proyecto_paa_buscador');
            var campoContratoId = document.getElementById('editar-objeto_proyecto_paa');
            var iconoContrato = document.getElementById('editar-contrato-comun-info');

            if (gasto.objeto_proyecto_paa) {
                var opcionContrato = document.querySelector('#editar-contrato_comun_lista .selector-buscable-opcion[data-id="' + CSS.escape(gasto.objeto_proyecto_paa) + '"]');
                campoContratoTexto.value = gasto.objeto_proyecto_paa;
                campoContratoId.value = gasto.objeto_proyecto_paa;

                if (iconoContrato) {
                    if (opcionContrato && opcionContrato.dataset.descripcion) {
                        iconoContrato.title = opcionContrato.dataset.descripcion;
                        iconoContrato.classList.remove('oculto');
                    } else {
                        iconoContrato.title = '';
                        iconoContrato.classList.add('oculto');
                    }
                }
            } else {
                campoContratoTexto.value = '';
                campoContratoId.value = '';

                if (iconoContrato) {
                    iconoContrato.title = '';
                    iconoContrato.classList.add('oculto');
                }
            }

            var campoRubroTexto = document.getElementById('editar-rubro_buscador');
            var campoRubroId = document.getElementById('editar-rubro_id');

            if (gasto.rubro_id) {
                var opcionRubro = document.querySelector('#editar-rubro_lista .selector-buscable-opcion[data-id="' + gasto.rubro_id + '"]');
                campoRubroTexto.value = opcionRubro ? opcionRubro.dataset.texto : '';
                campoRubroId.value = gasto.rubro_id;
            } else {
                campoRubroTexto.value = '';
                campoRubroId.value = '';
            }

            var campoProyectoTexto = document.getElementById('editar-proyecto_buscador');
            var campoProyectoId = document.getElementById('editar-proyecto_id');

            if (gasto.proyecto_id) {
                var opcionProyecto = document.querySelector('#editar-proyecto_lista .selector-buscable-opcion[data-id="' + gasto.proyecto_id + '"]');
                campoProyectoTexto.value = opcionProyecto ? (opcionProyecto.dataset.mostrar || opcionProyecto.dataset.texto) : '';
                campoProyectoId.value = gasto.proyecto_id;
            } else {
                campoProyectoTexto.value = '';
                campoProyectoId.value = '';
            }

            var mesesSeleccionados = gasto.meses ? gasto.meses.split(',') : [];
            var envoltorio = formularioEditarGasto.querySelector('.calendario-meses-envoltorio');

            envoltorio.querySelectorAll('input[name="meses[]"]').forEach(function (casilla) {
                casilla.checked = mesesSeleccionados.indexOf(casilla.value) !== -1;
            });

            if (envoltorio.actualizarDistribucion) {
                envoltorio.actualizarDistribucion();
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var formularioEdicion = document.getElementById('form-editar-gasto');

    if (!formularioEdicion) {
        return;
    }

    var formularioSucio = false;

    // El auto-poblado del formulario (botón oculto + auto-click) también corre en un
    // setTimeout(0) desde un <script> anterior a este archivo — se espera un tick más
    // para no confundir ese poblado inicial (dispara eventos "change" sintéticos en los
    // selectores buscables) con una edición real del usuario.
    setTimeout(function () {
        formularioEdicion.addEventListener('input', function () { formularioSucio = true; });
        formularioEdicion.addEventListener('change', function () { formularioSucio = true; });
    }, 0);

    var modalConfirmar = document.getElementById('modal-confirmar-salir-edicion');
    var botonSeguirEditando = document.getElementById('boton-seguir-editando-edicion');
    var botonSalirSinGuardar = document.getElementById('boton-salir-sin-guardar-edicion');
    var accionPendiente = null;

    function abrirConfirmacion(callback) {
        if (!formularioSucio) {
            callback();
            return;
        }

        accionPendiente = callback;

        if (modalConfirmar) {
            modalConfirmar.classList.add('abierto');
        } else {
            callback();
        }
    }

    function cerrarConfirmacion() {
        accionPendiente = null;

        if (modalConfirmar) {
            modalConfirmar.classList.remove('abierto');
        }
    }

    if (botonSeguirEditando) {
        botonSeguirEditando.addEventListener('click', cerrarConfirmacion);
    }

    if (botonSalirSinGuardar) {
        botonSalirSinGuardar.addEventListener('click', function () {
            var callback = accionPendiente;
            formularioSucio = false;
            cerrarConfirmacion();

            if (callback) {
                callback();
            }
        });
    }

    var enlaceVolver = document.querySelector('.barra-modulo-volver');

    if (enlaceVolver) {
        enlaceVolver.addEventListener('click', function (evento) {
            if (formularioSucio) {
                evento.preventDefault();
                abrirConfirmacion(function () {
                    window.location.href = enlaceVolver.href;
                });
            }
        });
    }

    var botonNuevoItem = document.getElementById('boton-nuevo-item-desde-edicion');
    var modalGasto = document.getElementById('modal-gasto');

    if (botonNuevoItem && modalGasto) {
        botonNuevoItem.addEventListener('click', function () {
            abrirConfirmacion(function () {
                modalGasto.classList.add('abierto');
            });
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.form-eliminar-egreso').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            if (!confirm('¿Eliminar este egreso? Esta acción no se puede deshacer.')) {
                evento.preventDefault();
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var formularioEditarEgreso = document.getElementById('form-editar-egreso');

    if (!formularioEditarEgreso) {
        return;
    }

    function asignar(id, valor) {
        var campo = document.getElementById(id);

        if (campo && valor !== undefined) {
            campo.value = valor;
        }
    }

    function cerrarEditarEgreso() {
        modalEditarEgreso.classList.remove('abierto');
    }

    document.querySelectorAll('.boton-editar-egreso').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var gasto;

            try {
                gasto = JSON.parse(boton.dataset.gasto);
            } catch (error) {
                return;
            }

            asignar('editar-egreso-id', gasto.id);
            asignar('editar-egreso-volver', gasto.volver || '');
            asignar('editar-egreso-autogestion_id', gasto.autogestion_id);
            asignar('editar-egreso-anio_presupuestal_id', gasto.anio_presupuestal_id);
            asignar('editar-egreso-categoria', gasto.categoria);
            asignar('editar-egreso-sede_id', gasto.sede_id);
            establecerValorBuscable('editar-egreso-dependencia', gasto.dependencia);
            asignar('editar-egreso-actividad', gasto.actividad);
            asignar('editar-egreso-insumo', gasto.insumo);
            asignar('editar-egreso-cantidad', gasto.cantidad);
            asignar('editar-egreso-costo_unitario', gasto.costo_unitario);

            var campoContratoTexto = document.getElementById('editar-egreso-objeto_proyecto_paa_buscador');
            var campoContratoId = document.getElementById('editar-egreso-objeto_proyecto_paa');
            var iconoContrato = document.getElementById('editar-egreso-contrato-comun-info');

            if (gasto.objeto_proyecto_paa && campoContratoTexto && campoContratoId) {
                var opcionContrato = document.querySelector('#editar-egreso-contrato_comun_lista .selector-buscable-opcion[data-id="' + CSS.escape(gasto.objeto_proyecto_paa) + '"]');
                campoContratoTexto.value = gasto.objeto_proyecto_paa;
                campoContratoId.value = gasto.objeto_proyecto_paa;

                if (iconoContrato) {
                    if (opcionContrato && opcionContrato.dataset.descripcion) {
                        iconoContrato.title = opcionContrato.dataset.descripcion;
                        iconoContrato.classList.remove('oculto');
                    } else {
                        iconoContrato.title = '';
                        iconoContrato.classList.add('oculto');
                    }
                }
            } else if (campoContratoTexto && campoContratoId) {
                campoContratoTexto.value = '';
                campoContratoId.value = '';

                if (iconoContrato) {
                    iconoContrato.title = '';
                    iconoContrato.classList.add('oculto');
                }
            }

            var campoRubroTexto = document.getElementById('editar-egreso-rubro_buscador');
            var campoRubroId = document.getElementById('editar-egreso-rubro_id');

            if (gasto.rubro_id && campoRubroTexto && campoRubroId) {
                var opcionRubro = document.querySelector('#editar-egreso-rubro_lista .selector-buscable-opcion[data-id="' + gasto.rubro_id + '"]');
                campoRubroTexto.value = opcionRubro ? opcionRubro.dataset.texto : '';
                campoRubroId.value = gasto.rubro_id;
            } else if (campoRubroTexto && campoRubroId) {
                campoRubroTexto.value = '';
                campoRubroId.value = '';
            }

            var campoProyectoTexto = document.getElementById('editar-egreso-proyecto_buscador');
            var campoProyectoId = document.getElementById('editar-egreso-proyecto_id');

            if (gasto.proyecto_id && campoProyectoTexto && campoProyectoId) {
                var opcionProyecto = document.querySelector('#editar-egreso-proyecto_lista .selector-buscable-opcion[data-id="' + gasto.proyecto_id + '"]');
                campoProyectoTexto.value = opcionProyecto ? (opcionProyecto.dataset.mostrar || opcionProyecto.dataset.texto) : '';
                campoProyectoId.value = gasto.proyecto_id;
            } else if (campoProyectoTexto && campoProyectoId) {
                campoProyectoTexto.value = '';
                campoProyectoId.value = '';
            }

            var mesesSeleccionados = gasto.meses ? gasto.meses.split(',') : [];
            var envoltorio = formularioEditarEgreso.querySelector('.calendario-meses-envoltorio');

            if (envoltorio) {
                envoltorio.querySelectorAll('input[name="meses[]"]').forEach(function (casilla) {
                    casilla.checked = mesesSeleccionados.indexOf(casilla.value) !== -1;
                });

                if (envoltorio.actualizarDistribucion) {
                    envoltorio.actualizarDistribucion();
                }
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.form-eliminar-ingreso').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            if (!confirm('¿Eliminar este ingreso? Esta acción no se puede deshacer.')) {
                evento.preventDefault();
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var formularioEditarIngreso = document.getElementById('form-editar-ingreso');

    if (!formularioEditarIngreso) {
        return;
    }

    function asignar(id, valor) {
        var campo = document.getElementById(id);

        if (campo && valor !== undefined) {
            campo.value = valor;
        }
    }

    document.querySelectorAll('.boton-editar-ingreso').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var ingreso;

            try {
                ingreso = JSON.parse(boton.dataset.ingreso);
            } catch (error) {
                return;
            }

            asignar('editar-ingreso-id', ingreso.id);
            asignar('editar-ingreso-volver', ingreso.volver || '');
            asignar('editar-ingreso-autogestion_id', ingreso.autogestion_id);
            asignar('editar-ingreso-anio_presupuestal_id', ingreso.anio_presupuestal_id);
            establecerValorBuscable('editar-ingreso-dependencia', ingreso.dependencia);
            asignar('editar-ingreso-concepto_adicional', ingreso.concepto_adicional);
            asignar('editar-ingreso-valor_adicional', ingreso.valor_adicional);

            var contenedorFilas = formularioEditarIngreso.querySelector('.filas-conceptos');

            if (contenedorFilas) {
                var filaBase = contenedorFilas.querySelector('.fila-concepto');
                var conceptos = Array.isArray(ingreso.conceptos) && ingreso.conceptos.length > 0
                    ? ingreso.conceptos
                    : [{}];

                contenedorFilas.innerHTML = '';

                conceptos.forEach(function (concepto) {
                    var filaNueva = filaBase.cloneNode(true);
                    filaNueva.querySelector('input[name="concepto[]"]').value = concepto.concepto || '';
                    filaNueva.querySelector('input[name="cantidad_concepto[]"]').value = concepto.cantidad || '';
                    filaNueva.querySelector('input[name="valor_concepto[]"]').value = concepto.valor || '';
                    contenedorFilas.appendChild(filaNueva);
                });
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var formularioEdicion = document.getElementById('form-editar-egreso') || document.getElementById('form-editar-ingreso');

    if (!formularioEdicion) {
        return;
    }

    var formularioSucio = false;

    setTimeout(function () {
        formularioEdicion.addEventListener('input', function () { formularioSucio = true; });
        formularioEdicion.addEventListener('change', function () { formularioSucio = true; });
    }, 0);

    var modalConfirmar = document.getElementById('modal-confirmar-salir-edicion');
    var botonSeguirEditando = document.getElementById('boton-seguir-editando-edicion');
    var botonSalirSinGuardar = document.getElementById('boton-salir-sin-guardar-edicion');
    var accionPendiente = null;

    function abrirConfirmacion(callback) {
        if (!formularioSucio) {
            callback();
            return;
        }

        accionPendiente = callback;

        if (modalConfirmar) {
            modalConfirmar.classList.add('abierto');
        } else {
            callback();
        }
    }

    function cerrarConfirmacion() {
        accionPendiente = null;

        if (modalConfirmar) {
            modalConfirmar.classList.remove('abierto');
        }
    }

    if (botonSeguirEditando) {
        botonSeguirEditando.addEventListener('click', cerrarConfirmacion);
    }

    if (botonSalirSinGuardar) {
        botonSalirSinGuardar.addEventListener('click', function () {
            var callback = accionPendiente;
            formularioSucio = false;
            cerrarConfirmacion();

            if (callback) {
                callback();
            }
        });
    }

    var enlaceVolver = document.querySelector('.barra-modulo-volver');

    if (enlaceVolver) {
        enlaceVolver.addEventListener('click', function (evento) {
            if (formularioSucio) {
                evento.preventDefault();
                abrirConfirmacion(function () {
                    window.location.href = enlaceVolver.href;
                });
            }
        });
    }

    var botonNuevoItem = document.getElementById('boton-nuevo-item-desde-edicion');
    var modalGasto = document.getElementById('modal-gasto');

    if (botonNuevoItem && modalGasto) {
        botonNuevoItem.addEventListener('click', function () {
            abrirConfirmacion(function () {
                modalGasto.classList.add('abierto');
            });
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    var modalArl = document.getElementById('modal-solicitud');
    var botonAbrirArl = document.getElementById('boton-abrir-modal-solicitud');
    var botonCerrarArl = document.getElementById('boton-cerrar-modal-solicitud');

    if (!modalArl) {
        return;
    }

    function abrirArl() {
        modalArl.classList.add('abierto');
    }

    function cerrarArl() {
        modalArl.classList.remove('abierto');
    }

    if (botonAbrirArl) {
        botonAbrirArl.addEventListener('click', abrirArl);
    }

    if (botonCerrarArl) {
        botonCerrarArl.addEventListener('click', cerrarArl);
    }

    modalArl.addEventListener('click', function (evento) {
        if (evento.target === modalArl) {
            cerrarArl();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarArl();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.form-eliminar-solicitud').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            if (!confirm('¿Eliminar esta solicitud? Esta acción no se puede deshacer.')) {
                evento.preventDefault();
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var formularioEditarArl = document.getElementById('form-editar-solicitud-arl');

    if (!formularioEditarArl) {
        return;
    }

    var numerosRomanos = ['I', 'II', 'III', 'IV', 'V'];

    document.querySelectorAll('.fila-menu-editar-solicitud').forEach(function (boton) {
        boton.addEventListener('click', function () {
            try {
                var solicitud = JSON.parse(boton.dataset.solicitud);
            } catch (error) {
                return;
            }

            document.getElementById('editar-solicitud-id').value = solicitud.id;
            document.getElementById('editar-solicitud-volver').value = solicitud.volver || '';
            document.getElementById('editar-solicitud-anio').value = solicitud.anio_presupuestal_id;
            establecerValorBuscable('editar-solicitud-facultad', solicitud.facultad);

            if (window.aplicarFiltroRolUsuario) {
                window.aplicarFiltroRolUsuario(document.getElementById('editar-solicitud-facultad'));
            }

            document.getElementById('editar-solicitud-rol').value = solicitud.rol_destinatario_id || '';

            numerosRomanos.forEach(function (numero, indice) {
                var nivel = indice + 1;
                var campoEstudiantes = document.getElementById('editar-solicitud-riesgo' + nivel + '_estudiantes');
                var campoValor = document.getElementById('editar-solicitud-riesgo' + nivel + '_valor');

                if (campoEstudiantes) {
                    campoEstudiantes.value = solicitud['riesgo' + nivel + '_estudiantes'] || 0;
                }

                if (campoValor) {
                    campoValor.value = parseFloat(solicitud['riesgo' + nivel + '_valor'] || 0).toFixed(2);
                }
            });
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modalMonitor = document.getElementById('modal-monitor');
    var botonAbrirMonitor = document.getElementById('boton-abrir-modal-monitor');
    var botonCerrarMonitor = document.getElementById('boton-cerrar-modal-monitor');

    if (modalMonitor) {
        if (botonAbrirMonitor) {
            botonAbrirMonitor.addEventListener('click', function () {
                modalMonitor.classList.add('abierto');
            });
        }

        if (botonCerrarMonitor) {
            botonCerrarMonitor.addEventListener('click', function () {
                modalMonitor.classList.remove('abierto');
            });
        }

        modalMonitor.addEventListener('click', function (evento) {
            if (evento.target === modalMonitor) {
                modalMonitor.classList.remove('abierto');
            }
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape') {
                modalMonitor.classList.remove('abierto');
            }
        });
    }

    var formularioEditarMonitor = document.getElementById('form-editar-monitor');

    if (formularioEditarMonitor) {
        document.querySelectorAll('.fila-menu-editar-monitor').forEach(function (boton) {
            boton.addEventListener('click', function () {
                try {
                    var monitor = JSON.parse(boton.dataset.monitor);
                } catch (error) {
                    return;
                }

                document.getElementById('editar-monitor-id').value = monitor.id;
                document.getElementById('editar-monitor-volver').value = monitor.volver || '';
                document.getElementById('editar-monitor-anio').value = monitor.anio_presupuestal_id;
                establecerValorBuscable('editar-monitor-dependencia', monitor.dependencia);

                if (window.aplicarFiltroRolUsuario) {
                    window.aplicarFiltroRolUsuario(document.getElementById('editar-monitor-dependencia'));
                }

                document.getElementById('editar-monitor-rol').value = monitor.rol_destinatario_id || '';
                document.getElementById('editar-monitor-tipo').value = monitor.tipo;
                document.getElementById('editar-monitor-semestre1').value = monitor.monitores_semestre1 || 0;
                document.getElementById('editar-monitor-semestre2').value = monitor.monitores_semestre2 || 0;
            });
        });
    }

    document.querySelectorAll('.form-eliminar-monitor').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            if (!confirm('¿Eliminar esta solicitud? Esta acción no se puede deshacer.')) {
                evento.preventDefault();
            }
        });
    });

});

document.addEventListener('DOMContentLoaded', function () {
    var modalOps = document.getElementById('modal-ops');
    var botonAbrirOps = document.getElementById('boton-abrir-modal-ops');
    var botonCerrarOps = document.getElementById('boton-cerrar-modal-ops');

    if (modalOps) {
        if (botonAbrirOps) {
            botonAbrirOps.addEventListener('click', function () {
                modalOps.classList.add('abierto');
            });
        }

        if (botonCerrarOps) {
            botonCerrarOps.addEventListener('click', function () {
                modalOps.classList.remove('abierto');
            });
        }

        modalOps.addEventListener('click', function (evento) {
            if (evento.target === modalOps) {
                modalOps.classList.remove('abierto');
            }
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape') {
                modalOps.classList.remove('abierto');
            }
        });
    }

    var formularioEditarOps = document.getElementById('form-editar-ops');

    if (formularioEditarOps) {
        document.querySelectorAll('.fila-menu-editar-ops').forEach(function (boton) {
            boton.addEventListener('click', function () {
                try {
                    var ops = JSON.parse(boton.dataset.ops);
                } catch (error) {
                    return;
                }

                document.getElementById('editar-ops-id').value = ops.id;
                document.getElementById('editar-ops-volver').value = ops.volver || '';
                document.getElementById('editar-ops-anio').value = ops.anio_presupuestal_id;
                document.getElementById('editar-ops-sede').value = ops.sede_id;
                establecerValorBuscable('editar-ops-proyecto_id', ops.proyecto_id);
                establecerValorBuscable('editar-ops-dependencia', ops.dependencia);

                if (window.aplicarFiltroRolUsuario) {
                    window.aplicarFiltroRolUsuario(document.getElementById('editar-ops-dependencia'));
                }

                document.getElementById('editar-ops-rol').value = ops.rol_destinatario_id || '';
                establecerValorBuscable('editar-ops-rubro_id', ops.rubro_id);
                document.getElementById('editar-ops-perfil').value = ops.perfil;
                document.getElementById('editar-ops-valor').value = ops.valor || 0;
                document.getElementById('editar-ops-cantidad').value = ops.cantidad;
                document.getElementById('editar-ops-observaciones').value = ops.observaciones || '';
            });
        });
    }

    document.querySelectorAll('.form-eliminar-ops').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            if (!confirm('¿Eliminar esta solicitud? Esta acción no se puede deshacer.')) {
                evento.preventDefault();
            }
        });
    });

});

document.addEventListener('DOMContentLoaded', function () {
    var modalPeticion = document.getElementById('modal-peticion');
    var botonAbrirPeticion = document.getElementById('boton-abrir-modal-peticion');
    var botonCerrarPeticion = document.getElementById('boton-cerrar-modal-peticion');

    if (modalPeticion) {
        if (botonAbrirPeticion) {
            botonAbrirPeticion.addEventListener('click', function () {
                modalPeticion.classList.add('abierto');
            });
        }

        if (botonCerrarPeticion) {
            botonCerrarPeticion.addEventListener('click', function () {
                modalPeticion.classList.remove('abierto');
            });
        }

        modalPeticion.addEventListener('click', function (evento) {
            if (evento.target === modalPeticion) {
                modalPeticion.classList.remove('abierto');
            }
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape') {
                modalPeticion.classList.remove('abierto');
            }
        });
    }

    var formularioEditarPeticion = document.getElementById('form-editar-peticion');

    if (formularioEditarPeticion) {
        document.querySelectorAll('.fila-menu-editar-peticion').forEach(function (boton) {
            boton.addEventListener('click', function () {
                try {
                    var peticion = JSON.parse(boton.dataset.peticion);
                } catch (error) {
                    return;
                }

                document.getElementById('editar-peticion-id').value = peticion.id;
                document.getElementById('editar-peticion-volver').value = peticion.volver || '';
                document.getElementById('editar-peticion-anio').value = peticion.anio_presupuestal_id;
                document.getElementById('editar-peticion-concepto').value = peticion.concepto;
                document.getElementById('editar-peticion-rol').value = peticion.rol_destinatario_id || '';
                document.getElementById('editar-peticion-semestre1').value = peticion.semestre1 || 0;
                document.getElementById('editar-peticion-valor-s1').value = peticion.valor_s1 || 0;
                document.getElementById('editar-peticion-semestre2').value = peticion.semestre2 || 0;
                document.getElementById('editar-peticion-valor-s2').value = peticion.valor_s2 || 0;
            });
        });
    }

    document.querySelectorAll('.form-eliminar-peticion').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            if (!confirm('¿Eliminar esta petición? Esta acción no se puede deshacer.')) {
                evento.preventDefault();
            }
        });
    });

});

document.addEventListener('DOMContentLoaded', function () {
    var formularioEdicion = document.getElementById('form-editar-solicitud-arl')
        || document.getElementById('form-editar-monitor')
        || document.getElementById('form-editar-ops')
        || document.getElementById('form-editar-peticion');

    if (!formularioEdicion) {
        return;
    }

    var formularioSucio = false;

    setTimeout(function () {
        formularioEdicion.addEventListener('input', function () { formularioSucio = true; });
        formularioEdicion.addEventListener('change', function () { formularioSucio = true; });
    }, 0);

    var modalConfirmar = document.getElementById('modal-confirmar-salir-edicion');
    var botonSeguirEditando = document.getElementById('boton-seguir-editando-edicion');
    var botonSalirSinGuardar = document.getElementById('boton-salir-sin-guardar-edicion');
    var accionPendiente = null;

    function abrirConfirmacion(callback) {
        if (!formularioSucio) {
            callback();
            return;
        }

        accionPendiente = callback;

        if (modalConfirmar) {
            modalConfirmar.classList.add('abierto');
        } else {
            callback();
        }
    }

    function cerrarConfirmacion() {
        accionPendiente = null;

        if (modalConfirmar) {
            modalConfirmar.classList.remove('abierto');
        }
    }

    if (botonSeguirEditando) {
        botonSeguirEditando.addEventListener('click', cerrarConfirmacion);
    }

    if (botonSalirSinGuardar) {
        botonSalirSinGuardar.addEventListener('click', function () {
            var callback = accionPendiente;
            formularioSucio = false;
            cerrarConfirmacion();

            if (callback) {
                callback();
            }
        });
    }

    var enlaceVolver = document.querySelector('.barra-modulo-volver');

    if (enlaceVolver) {
        enlaceVolver.addEventListener('click', function (evento) {
            if (formularioSucio) {
                evento.preventDefault();
                abrirConfirmacion(function () {
                    window.location.href = enlaceVolver.href;
                });
            }
        });
    }

    var botonNuevoItem = document.getElementById('boton-nuevo-item-desde-edicion');
    var mapaModalCrear = {
        'form-editar-solicitud-arl': 'modal-solicitud',
        'form-editar-monitor': 'modal-monitor',
        'form-editar-ops': 'modal-ops',
        'form-editar-peticion': 'modal-peticion',
    };
    var modalCrear = document.getElementById(mapaModalCrear[formularioEdicion.id] || '');

    if (botonNuevoItem && modalCrear) {
        botonNuevoItem.addEventListener('click', function () {
            abrirConfirmacion(function () {
                modalCrear.classList.add('abierto');
            });
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-smlv-por-anio]').forEach(function (formulario) {
        var smlvPorAnio = {};
        var porcentajesRiesgo = {};

        try {
            smlvPorAnio = JSON.parse(formulario.dataset.smlvPorAnio || '{}');
            porcentajesRiesgo = JSON.parse(formulario.dataset.porcentajesRiesgo || '{}');
        } catch (error) {
            smlvPorAnio = {};
            porcentajesRiesgo = {};
        }

        var selectAnio = formulario.querySelector('select[name="anio_presupuestal_id"]');
        var camposEstudiantes = formulario.querySelectorAll('.campo-solicitud-estudiantes');

        function recalcular() {
            var smlv = parseFloat(smlvPorAnio[selectAnio.value]) || 0;

            camposEstudiantes.forEach(function (campo) {
                var nivel = campo.dataset.nivel;
                var estudiantes = parseInt(campo.value, 10) || 0;
                var porcentaje = parseFloat(porcentajesRiesgo[nivel]) || 0;
                var valor = estudiantes * smlv * (porcentaje / 100) * 12;
                var campoValor = formulario.querySelector('[id$="riesgo' + nivel + '_valor"]');

                if (campoValor) {
                    campoValor.value = valor.toFixed(2);
                }
            });
        }

        if (selectAnio) {
            selectAnio.addEventListener('change', recalcular);
        }

        camposEstudiantes.forEach(function (campo) {
            campo.addEventListener('input', recalcular);
        });

        recalcular();
    });
});

document.addEventListener('DOMContentLoaded', function () {
    function formatoMoneda(valor) {
        return '$' + valor.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    document.querySelectorAll('.calendario-meses-envoltorio').forEach(function (envoltorio) {
        var formulario = envoltorio.closest('form');

        if (!formulario) {
            return;
        }

        var casillasMes = envoltorio.querySelectorAll('.calendario-meses-grilla input[name="meses[]"]');
        var inputCantidad = formulario.querySelector('input[name="cantidad"]');
        var inputCostoUnitario = formulario.querySelector('input[name="costo_unitario"]');
        var lista = envoltorio.querySelector('.lista-meses-seleccionados');
        var totalSpan = envoltorio.querySelector('.resumen-meses-total-valor');

        if (!casillasMes.length || !lista) {
            return;
        }

        function actualizarDistribucion() {
            var cantidad = parseFloat(inputCantidad ? inputCantidad.value : '') || 0;
            var costoUnitario = parseFloat(inputCostoUnitario ? inputCostoUnitario.value : '') || 0;
            var total = cantidad * costoUnitario;

            var seleccionados = Array.prototype.filter.call(casillasMes, function (casilla) {
                return casilla.checked;
            });

            lista.innerHTML = '';

            if (seleccionados.length === 0) {
                var vacio = document.createElement('li');
                vacio.className = 'lista-meses-vacio';
                vacio.textContent = 'Selecciona los meses de ejecución.';
                lista.appendChild(vacio);
            } else {
                var montoPorMes = total / seleccionados.length;

                seleccionados.forEach(function (casilla) {
                    var celda = casilla.closest('.mes-celda');
                    var nombreMes = celda ? celda.querySelector('span').textContent : '';

                    var nombreSpan = document.createElement('span');
                    nombreSpan.textContent = nombreMes;

                    var montoSpan = document.createElement('span');
                    montoSpan.textContent = formatoMoneda(montoPorMes);

                    var item = document.createElement('li');
                    item.appendChild(nombreSpan);
                    item.appendChild(montoSpan);
                    lista.appendChild(item);
                });
            }

            if (totalSpan) {
                totalSpan.textContent = formatoMoneda(total);
            }
        }

        casillasMes.forEach(function (casilla) {
            casilla.addEventListener('change', actualizarDistribucion);
        });

        if (inputCantidad) {
            inputCantidad.addEventListener('input', actualizarDistribucion);
        }

        if (inputCostoUnitario) {
            inputCostoUnitario.addEventListener('input', actualizarDistribucion);
        }

        envoltorio.dataset.actualizarDistribucion = 'listo';
        envoltorio.actualizarDistribucion = actualizarDistribucion;

        actualizarDistribucion();
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.filas-conceptos').forEach(function (contenedorFilas) {
        var botonAgregarFila = contenedorFilas.parentElement.querySelector('.boton-agregar-fila');

        if (!botonAgregarFila) {
            return;
        }

        function limpiarFila(fila) {
            fila.querySelectorAll('input').forEach(function (input) {
                input.value = '';
            });
        }

        botonAgregarFila.addEventListener('click', function () {
            var filaBase = contenedorFilas.querySelector('.fila-concepto');
            var filaNueva = filaBase.cloneNode(true);
            limpiarFila(filaNueva);
            contenedorFilas.appendChild(filaNueva);
        });

        contenedorFilas.addEventListener('click', function (evento) {
            var boton = evento.target.closest('.boton-quitar-fila');

            if (!boton) {
                return;
            }

            var filas = contenedorFilas.querySelectorAll('.fila-concepto');

            if (filas.length > 1) {
                boton.closest('.fila-concepto').remove();
            } else {
                limpiarFila(boton.closest('.fila-concepto'));
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var contenedores = document.querySelectorAll('.selector-buscable');

    contenedores.forEach(function (contenedor) {
        var input = contenedor.querySelector('.selector-buscable-input');
        var oculto = contenedor.querySelector('input[type="hidden"]');
        var lista = contenedor.querySelector('.selector-buscable-lista');
        var vacio = contenedor.querySelector('.selector-buscable-vacio');
        var opciones = Array.prototype.slice.call(contenedor.querySelectorAll('.selector-buscable-opcion'));
        var campo = contenedor.closest('.campo');
        var icono = campo ? campo.querySelector('.icono-info') : null;

        if (!input || !oculto || !lista) {
            return;
        }

        function actualizarIcono(opcion) {
            if (!icono) {
                return;
            }

            if (opcion && opcion.dataset.descripcion) {
                icono.title = opcion.dataset.descripcion;
                icono.classList.remove('oculto');
            } else {
                icono.title = '';
                icono.classList.add('oculto');
            }
        }

        function filtrar() {
            var texto = input.value.trim().toLowerCase();
            var tipoFiltro = contenedor.dataset.tipoFiltro || '';
            var algunaVisible = false;

            opciones.forEach(function (opcion) {
                var coincideTexto = opcion.dataset.texto.toLowerCase().indexOf(texto) !== -1;
                var coincideTipo = !tipoFiltro || opcion.dataset.tipo === tipoFiltro;
                var visible = coincideTexto && coincideTipo;
                opcion.classList.toggle('oculta', !visible);

                if (visible) {
                    algunaVisible = true;
                }
            });

            if (vacio) {
                vacio.style.display = algunaVisible ? 'none' : 'block';
            }
        }

        contenedor.aplicarFiltroTipo = function (tipo) {
            contenedor.dataset.tipoFiltro = tipo || '';

            if (oculto.value !== '') {
                var opcionActual = contenedor.querySelector('.selector-buscable-opcion[data-id="' + CSS.escape(oculto.value) + '"]');
                var sigueValido = opcionActual && (!tipo || opcionActual.dataset.tipo === tipo);

                if (!sigueValido) {
                    oculto.value = '';
                    input.value = '';
                    actualizarIcono(null);
                    oculto.dispatchEvent(new Event('change'));
                }
            }

            if (lista.classList.contains('abierta')) {
                filtrar();
            }
        };

        function abrir() {
            lista.classList.add('abierta');
            filtrar();
        }

        function cerrar() {
            lista.classList.remove('abierta');
        }

        input.addEventListener('focus', abrir);

        input.addEventListener('input', function () {
            oculto.value = '';
            abrir();
            actualizarIcono(null);
        });

        lista.addEventListener('mousedown', function (evento) {
            evento.preventDefault();
        });

        lista.addEventListener('click', function (evento) {
            var opcion = evento.target.closest('.selector-buscable-opcion');

            if (!opcion) {
                return;
            }

            input.value = opcion.dataset.mostrar || opcion.dataset.texto;
            oculto.value = opcion.dataset.id;
            actualizarIcono(opcion);
            cerrar();
            oculto.dispatchEvent(new Event('change'));
        });

        document.addEventListener('click', function (evento) {
            if (!contenedor.contains(evento.target)) {
                cerrar();
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var menusFila = document.querySelectorAll('.fila-menu');

    if (menusFila.length === 0) {
        return;
    }

    function cerrarTodos(exceptoEste) {
        menusFila.forEach(function (menu) {
            if (menu !== exceptoEste) {
                menu.querySelector('.fila-menu-dropdown').classList.remove('abierto');
            }
        });
    }

    menusFila.forEach(function (menu) {
        var boton = menu.querySelector('.fila-menu-boton');
        var dropdown = menu.querySelector('.fila-menu-dropdown');

        boton.addEventListener('click', function (evento) {
            evento.stopPropagation();
            cerrarTodos(menu);
            dropdown.classList.toggle('abierto');
        });
    });

    document.addEventListener('click', function () {
        cerrarTodos(null);
    });

    document.querySelectorAll('.form-eliminar-jerarquia').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            if (!confirm('¿Eliminar esta jerarquía? Esta acción no se puede deshacer.')) {
                evento.preventDefault();
            }
        });
    });

    var datosRolesPorTipoJerarquia = document.getElementById('datos-roles-por-tipo-jerarquia');
    var rolesPorTipoJerarquia = {};

    if (datosRolesPorTipoJerarquia) {
        try {
            rolesPorTipoJerarquia = JSON.parse(datosRolesPorTipoJerarquia.textContent || '{}');
        } catch (error) {
            rolesPorTipoJerarquia = {};
        }
    }

    function aplicarRolesDeTipo(selectTipo) {
        var selectRoles = document.getElementById(selectTipo.dataset.selectRoles || '');

        if (!selectRoles) {
            return;
        }

        var permitidos = rolesPorTipoJerarquia[selectTipo.value] ? rolesPorTipoJerarquia[selectTipo.value].map(String) : [];

        selectRoles.querySelectorAll('input[type="checkbox"]').forEach(function (casilla) {
            casilla.checked = permitidos.indexOf(casilla.value) !== -1;
        });
    }

    document.querySelectorAll('[data-select-roles]').forEach(function (selectTipo) {
        selectTipo.addEventListener('change', function () {
            aplicarRolesDeTipo(selectTipo);
        });
    });

    var modalEditar = document.getElementById('modal-editar-jerarquia');
    var botonCerrarEditar = document.getElementById('boton-cerrar-modal-editar-jerarquia');
    var campoId = document.getElementById('editar-jerarquia-id');
    var campoNombre = document.getElementById('editar-jerarquia-nombre');
    var campoPadre = document.getElementById('editar-jerarquia-padre');
    var campoTipo = document.getElementById('editar-jerarquia-tipo');

    if (!modalEditar) {
        return;
    }

    document.querySelectorAll('.fila-menu-editar').forEach(function (boton) {
        boton.addEventListener('click', function () {
            cerrarTodos(null);
            campoId.value = boton.dataset.id;
            campoNombre.value = boton.dataset.nombre;
            campoPadre.value = boton.dataset.padreId;
            campoTipo.value = boton.dataset.tipo || '';
            aplicarRolesDeTipo(campoTipo);
            modalEditar.classList.add('abierto');
        });
    });

    if (botonCerrarEditar) {
        botonCerrarEditar.addEventListener('click', function () {
            modalEditar.classList.remove('abierto');
        });
    }

    modalEditar.addEventListener('click', function (evento) {
        if (evento.target === modalEditar) {
            modalEditar.classList.remove('abierto');
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            modalEditar.classList.remove('abierto');
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.form-enviar-solicitud').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            if (!confirm('¿Enviar esta solicitud al rol destinatario configurado?')) {
                evento.preventDefault();
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.form-enviar-gasto').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            if (!confirm('¿Enviar este gasto para revisión en Peticiones?')) {
                evento.preventDefault();
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modalEditar = document.getElementById('modal-editar-dependencia');

    if (!modalEditar) {
        return;
    }

    var botonCerrarEditar = document.getElementById('boton-cerrar-modal-editar-dependencia');
    var campoId = document.getElementById('editar-dependencia-id');
    var campoCodigo = document.getElementById('editar-dependencia-codigo');
    var campoNombre = document.getElementById('editar-dependencia-nombre');
    var campoTipo = document.getElementById('editar-dependencia-tipo');

    function cerrarEditar() {
        modalEditar.classList.remove('abierto');
    }

    document.querySelectorAll('.boton-editar-dependencia').forEach(function (boton) {
        boton.addEventListener('click', function () {
            campoId.value = boton.dataset.id;
            campoCodigo.value = boton.dataset.codigo;
            campoNombre.value = boton.dataset.nombre;
            campoTipo.value = boton.dataset.tipo;
            establecerValorBuscable('editar-dependencia-flujo', boton.dataset.flujoId || '');
            modalEditar.classList.add('abierto');
        });
    });

    if (botonCerrarEditar) {
        botonCerrarEditar.addEventListener('click', cerrarEditar);
    }

    modalEditar.addEventListener('click', function (evento) {
        if (evento.target === modalEditar) {
            cerrarEditar();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarEditar();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modalEditar = document.getElementById('modal-editar-rol');

    if (!modalEditar) {
        return;
    }

    var botonCerrarEditar = document.getElementById('boton-cerrar-modal-editar-rol');
    var campoId = document.getElementById('editar-rol-id');
    var campoNombre = document.getElementById('editar-rol-nombre');
    var campoOrden = document.getElementById('editar-rol-orden');

    function cerrarEditar() {
        modalEditar.classList.remove('abierto');
    }

    document.querySelectorAll('.boton-editar-rol').forEach(function (boton) {
        boton.addEventListener('click', function () {
            campoId.value = boton.dataset.id;
            campoNombre.value = boton.dataset.nombre;
            campoOrden.value = boton.dataset.orden;
            modalEditar.classList.add('abierto');
        });
    });

    if (botonCerrarEditar) {
        botonCerrarEditar.addEventListener('click', cerrarEditar);
    }

    modalEditar.addEventListener('click', function (evento) {
        if (evento.target === modalEditar) {
            cerrarEditar();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarEditar();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modalEditarEstamento = document.getElementById('modal-editar-estamento');

    if (!modalEditarEstamento) {
        return;
    }

    var botonCerrarEditarEstamento = document.getElementById('boton-cerrar-modal-editar-estamento');
    var campoEstamentoId = document.getElementById('editar-estamento-id');
    var campoEstamentoNombre = document.getElementById('editar-estamento-nombre');

    function cerrarEditarEstamento() {
        modalEditarEstamento.classList.remove('abierto');
    }

    document.querySelectorAll('.boton-editar-estamento').forEach(function (boton) {
        boton.addEventListener('click', function () {
            campoEstamentoId.value = boton.dataset.id;
            campoEstamentoNombre.value = boton.dataset.nombre;
            modalEditarEstamento.classList.add('abierto');
        });
    });

    if (botonCerrarEditarEstamento) {
        botonCerrarEditarEstamento.addEventListener('click', cerrarEditarEstamento);
    }

    modalEditarEstamento.addEventListener('click', function (evento) {
        if (evento.target === modalEditarEstamento) {
            cerrarEditarEstamento();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarEditarEstamento();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modalAdmin = document.getElementById('modal-administrador');

    if (!modalAdmin) {
        return;
    }

    var botonAbrirAdmin = document.getElementById('boton-abrir-modal-administrador');
    var botonCerrarAdmin = document.getElementById('boton-cerrar-modal-administrador');

    function cerrarAdmin() {
        modalAdmin.classList.remove('abierto');
    }

    if (botonAbrirAdmin) {
        botonAbrirAdmin.addEventListener('click', function () {
            modalAdmin.classList.add('abierto');
        });
    }

    if (botonCerrarAdmin) {
        botonCerrarAdmin.addEventListener('click', cerrarAdmin);
    }

    modalAdmin.addEventListener('click', function (evento) {
        if (evento.target === modalAdmin) {
            cerrarAdmin();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarAdmin();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var datosElemento = document.getElementById('datos-roles-por-tipo');

    if (!datosElemento) {
        return;
    }

    var rolesPorTipo = {};
    try {
        rolesPorTipo = JSON.parse(datosElemento.textContent || '{}');
    } catch (error) {
        rolesPorTipo = {};
    }

    function obtenerTipoDependenciaSeleccionada(campoDependencia) {
        if (campoDependencia.tagName === 'SELECT') {
            var opcion = campoDependencia.options[campoDependencia.selectedIndex];

            return opcion ? (opcion.dataset.tipo || '') : '';
        }

        var contenedorBuscable = campoDependencia.closest('.selector-buscable');

        if (!contenedorBuscable || campoDependencia.value === '') {
            return '';
        }

        var opcionElegida = contenedorBuscable.querySelector('.selector-buscable-opcion[data-id="' + CSS.escape(campoDependencia.value) + '"]');

        return opcionElegida ? (opcionElegida.dataset.tipo || '') : '';
    }

    function aplicarFiltroRol(selectDependencia) {
        var selectRol = document.getElementById(selectDependencia.dataset.selectRol || '');

        if (!selectRol) {
            return;
        }

        var tipo = obtenerTipoDependenciaSeleccionada(selectDependencia);
        var permitidos = tipo && rolesPorTipo[tipo] ? rolesPorTipo[tipo].map(String) : null;
        var valorActual = selectRol.value;
        var valorSigueValido = false;

        Array.prototype.forEach.call(selectRol.options, function (opcion) {
            if (opcion.value === '') {
                return;
            }

            var permitido = permitidos === null || permitidos.indexOf(opcion.value) !== -1;
            opcion.hidden = !permitido;

            if (permitido && opcion.value === valorActual) {
                valorSigueValido = true;
            }
        });

        if (!valorSigueValido) {
            selectRol.value = '';
        }
    }

    document.querySelectorAll('[data-select-rol]').forEach(function (selectDependencia) {
        selectDependencia.addEventListener('change', function () {
            aplicarFiltroRol(selectDependencia);
        });
    });

    function aplicarFiltroDependencia(selectTipo) {
        var campoDependencia = document.getElementById(selectTipo.dataset.selectDependencia || '');

        if (!campoDependencia) {
            return;
        }

        var tipo = selectTipo.value;

        if (campoDependencia.tagName === 'SELECT') {
            var valorActual = campoDependencia.value;
            var valorSigueValido = false;

            Array.prototype.forEach.call(campoDependencia.options, function (opcion) {
                if (opcion.value === '') {
                    return;
                }

                var permitido = tipo === '' || opcion.dataset.tipo === tipo;
                opcion.hidden = !permitido;

                if (permitido && opcion.value === valorActual) {
                    valorSigueValido = true;
                }
            });

            if (!valorSigueValido) {
                campoDependencia.value = '';
            }
        } else {
            var contenedorBuscable = campoDependencia.closest('.selector-buscable');

            if (contenedorBuscable && contenedorBuscable.aplicarFiltroTipo) {
                contenedorBuscable.aplicarFiltroTipo(tipo);
            }
        }

        aplicarFiltroRol(campoDependencia);
    }

    document.querySelectorAll('[data-select-dependencia]').forEach(function (selectTipo) {
        selectTipo.addEventListener('change', function () {
            aplicarFiltroDependencia(selectTipo);
        });
    });

    window.aplicarFiltroRolUsuario = aplicarFiltroRol;
});

document.addEventListener('DOMContentLoaded', function () {
    var datosUsuariosElemento = document.getElementById('datos-usuarios-por-dependencia-rol');

    if (!datosUsuariosElemento) {
        return;
    }

    var usuariosPorDependenciaYRol = {};
    try {
        usuariosPorDependenciaYRol = JSON.parse(datosUsuariosElemento.textContent || '{}');
    } catch (error) {
        usuariosPorDependenciaYRol = {};
    }

    document.querySelectorAll('.form-confirmar-envio').forEach(function (formulario) {
        var idCampoDependencia = formulario.dataset.campoDependencia;
        var idCampoRol = formulario.dataset.campoRol;

        if (!idCampoDependencia || !idCampoRol) {
            return;
        }

        formulario.addEventListener('submit', function (evento) {
            var campoDependencia = document.getElementById(idCampoDependencia);
            var campoRol = document.getElementById(idCampoRol);

            if (!campoDependencia || !campoRol) {
                return;
            }

            var dependencia = campoDependencia.value;
            var rol = campoRol.value;

            if (!dependencia || !rol) {
                return;
            }

            var candidatos = (usuariosPorDependenciaYRol[dependencia] && usuariosPorDependenciaYRol[dependencia][rol]) || [];
            var nombres = candidatos.map(function (usuario) {
                return usuario.nombre;
            });
            var mensaje = nombres.length > 0
                ? 'Se va a enviar a: ' + nombres.join(', ') + '. ¿Confirmar?'
                : 'No se encontró ningún usuario con ese rol en esa dependencia. ¿Enviar de todas formas?';

            if (!confirm(mensaje)) {
                evento.preventDefault();
            }
        });
    });
});

/**
 * Selector de destinatario específico: cuando una dependencia+rol elegidos en un formulario de
 * "enviar"/"redireccionar" tienen más de un usuario coincidente (más de un Gestor o Avalador),
 * muestra y exige un <select class="selector-destinatario" data-campo-dependencia="idCampoDep"
 * data-campo-rol="idCampoRol"> para que el usuario elija a cuál de ellos remitir la petición. Si
 * hay 0 o 1 coincidencia, el campo se oculta y no es obligatorio (0 lo maneja el backend con su
 * propio mensaje; 1 se envía automáticamente a esa única persona).
 */
document.addEventListener('DOMContentLoaded', function () {
    var datosUsuariosElemento = document.getElementById('datos-usuarios-por-dependencia-rol');
    var selectoresDestinatario = document.querySelectorAll('.selector-destinatario');

    if (!datosUsuariosElemento || selectoresDestinatario.length === 0) {
        return;
    }

    var usuariosPorDependenciaYRol = {};
    try {
        usuariosPorDependenciaYRol = JSON.parse(datosUsuariosElemento.textContent || '{}');
    } catch (error) {
        usuariosPorDependenciaYRol = {};
    }

    selectoresDestinatario.forEach(function (selectDestinatario) {
        var idCampoDependencia = selectDestinatario.dataset.campoDependencia;
        var idCampoRol = selectDestinatario.dataset.campoRol;
        var campoDependencia = idCampoDependencia ? document.getElementById(idCampoDependencia) : null;
        var campoRol = idCampoRol ? document.getElementById(idCampoRol) : null;
        var contenedor = selectDestinatario.closest('.campo');

        if (!campoDependencia || !campoRol || !contenedor) {
            return;
        }

        function actualizar() {
            var dependencia = campoDependencia.value;
            var rol = campoRol.value;
            var candidatos = (dependencia && rol && usuariosPorDependenciaYRol[dependencia] && usuariosPorDependenciaYRol[dependencia][rol]) || [];

            selectDestinatario.innerHTML = '';

            var opcionVacia = document.createElement('option');
            opcionVacia.value = '';
            opcionVacia.textContent = 'Selecciona a quién enviarlo';
            selectDestinatario.appendChild(opcionVacia);

            candidatos.forEach(function (usuario) {
                var opcion = document.createElement('option');
                opcion.value = usuario.id;
                opcion.textContent = usuario.nombre;
                selectDestinatario.appendChild(opcion);
            });

            if (candidatos.length > 1) {
                contenedor.style.display = '';
                selectDestinatario.required = true;
            } else {
                contenedor.style.display = 'none';
                selectDestinatario.required = false;
                selectDestinatario.value = '';
            }
        }

        campoDependencia.addEventListener('change', actualizar);
        campoRol.addEventListener('change', actualizar);
        actualizar();
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modalEditarUsuario = document.getElementById('modal-editar-usuario');

    if (!modalEditarUsuario) {
        return;
    }

    var botonCerrarEditarUsuario = document.getElementById('boton-cerrar-modal-editar-usuario');
    var campoUsuarioId = document.getElementById('editar-usuario-id');
    var campoUsuarioNombre = document.getElementById('editar-usuario-nombre');
    var campoUsuarioCorreo = document.getElementById('editar-usuario-correo');
    var campoUsuarioPassword = document.getElementById('editar-usuario-password');
    var campoUsuarioDependencia = document.getElementById('editar-usuario-dependencia');
    var campoUsuarioRol = document.getElementById('editar-usuario-rol');
    var campoUsuarioEstamento = document.getElementById('editar-usuario-estamento');

    function cerrarEditarUsuario() {
        modalEditarUsuario.classList.remove('abierto');
    }

    document.querySelectorAll('.boton-editar-usuario').forEach(function (boton) {
        boton.addEventListener('click', function () {
            campoUsuarioId.value = boton.dataset.id;
            campoUsuarioNombre.value = boton.dataset.nombre;
            campoUsuarioCorreo.value = boton.dataset.correo;

            if (campoUsuarioPassword) {
                campoUsuarioPassword.value = '';
            }

            if (campoUsuarioDependencia) {
                establecerValorBuscable(campoUsuarioDependencia.id, boton.dataset.dependenciaId && boton.dataset.dependenciaId !== '0' ? boton.dataset.dependenciaId : '');

                if (window.aplicarFiltroRolUsuario) {
                    window.aplicarFiltroRolUsuario(campoUsuarioDependencia);
                }
            }

            if (campoUsuarioRol) {
                campoUsuarioRol.value = boton.dataset.rolId && boton.dataset.rolId !== '0' ? boton.dataset.rolId : '';
            }

            if (campoUsuarioEstamento) {
                campoUsuarioEstamento.value = boton.dataset.estamentoId && boton.dataset.estamentoId !== '0' ? boton.dataset.estamentoId : '';
            }

            modalEditarUsuario.classList.add('abierto');
        });
    });

    if (botonCerrarEditarUsuario) {
        botonCerrarEditarUsuario.addEventListener('click', cerrarEditarUsuario);
    }

    modalEditarUsuario.addEventListener('click', function (evento) {
        if (evento.target === modalEditarUsuario) {
            cerrarEditarUsuario();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarEditarUsuario();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modalPermisosUsuario = document.getElementById('modal-permisos-usuario');

    if (!modalPermisosUsuario) {
        return;
    }

    var botonCerrarPermisos = document.getElementById('boton-cerrar-modal-permisos-usuario');
    var campoPermisosId = document.getElementById('permisos-usuario-id');
    var campoPermisosNombre = document.getElementById('permisos-usuario-nombre');
    var campoPermisosDependencia = document.getElementById('permisos-usuario-dependencia');
    var campoPermisosRol = document.getElementById('permisos-usuario-rol');
    var botonRestablecerMenu = document.getElementById('boton-restablecer-menu-permisos');

    var datosMenuPorTipoUsuarios = document.getElementById('datos-menu-por-tipo-usuarios');
    var menuPorTipoUsuarios = {};

    if (datosMenuPorTipoUsuarios) {
        try {
            menuPorTipoUsuarios = JSON.parse(datosMenuPorTipoUsuarios.textContent || '{}');
        } catch (error) {
            menuPorTipoUsuarios = {};
        }
    }

    function marcarMenuPermisos(clavesPermitidas) {
        modalPermisosUsuario.querySelectorAll('input[name="menu[]"]').forEach(function (casilla) {
            casilla.checked = clavesPermitidas.indexOf(casilla.value) !== -1;
        });
    }

    function cerrarPermisos() {
        modalPermisosUsuario.classList.remove('abierto');
    }

    document.querySelectorAll('.boton-permisos-usuario').forEach(function (boton) {
        boton.addEventListener('click', function () {
            campoPermisosId.value = boton.dataset.id;
            campoPermisosNombre.textContent = boton.dataset.nombre;
            establecerValorBuscable(campoPermisosDependencia.id, boton.dataset.dependenciaId && boton.dataset.dependenciaId !== '0' ? boton.dataset.dependenciaId : '');

            if (window.aplicarFiltroRolUsuario) {
                window.aplicarFiltroRolUsuario(campoPermisosDependencia);
            }

            campoPermisosRol.value = boton.dataset.rolId && boton.dataset.rolId !== '0' ? boton.dataset.rolId : '';

            var menuEfectivo = [];
            try {
                menuEfectivo = JSON.parse(boton.dataset.menuEfectivo || '[]');
            } catch (error) {
                menuEfectivo = [];
            }
            marcarMenuPermisos(menuEfectivo);

            modalPermisosUsuario.classList.add('abierto');
        });
    });

    if (botonRestablecerMenu) {
        botonRestablecerMenu.addEventListener('click', function () {
            var opcionDependencia = campoPermisosDependencia.options[campoPermisosDependencia.selectedIndex];
            var tipo = opcionDependencia ? opcionDependencia.dataset.tipo : '';
            var rolId = campoPermisosRol.value;
            var plantillasTipo = (tipo && menuPorTipoUsuarios[tipo]) ? menuPorTipoUsuarios[tipo] : {};
            var permitidos = (rolId && plantillasTipo[rolId]) ? plantillasTipo[rolId] : (plantillasTipo.general || []);
            marcarMenuPermisos(permitidos);
        });
    }

    if (botonCerrarPermisos) {
        botonCerrarPermisos.addEventListener('click', cerrarPermisos);
    }

    modalPermisosUsuario.addEventListener('click', function (evento) {
        if (evento.target === modalPermisosUsuario) {
            cerrarPermisos();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarPermisos();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modalMenuTipo = document.getElementById('modal-menu-tipo');

    if (!modalMenuTipo) {
        return;
    }

    var datosMenuPorTipoElemento = document.getElementById('datos-menu-por-tipo');
    var menuPorTipo = {};

    if (datosMenuPorTipoElemento) {
        try {
            menuPorTipo = JSON.parse(datosMenuPorTipoElemento.textContent || '{}');
        } catch (error) {
            menuPorTipo = {};
        }
    }

    var datosRolesPorTipoElemento = document.getElementById('datos-roles-por-tipo-jerarquia');
    var rolesPorTipo = {};

    if (datosRolesPorTipoElemento) {
        try {
            rolesPorTipo = JSON.parse(datosRolesPorTipoElemento.textContent || '{}');
        } catch (error) {
            rolesPorTipo = {};
        }
    }

    var botonCerrarMenuTipo = document.getElementById('boton-cerrar-modal-menu-tipo');
    var campoMenuTipoNombre = document.getElementById('menu-tipo-nombre');
    var campoMenuTipoValor = document.getElementById('menu-tipo-valor');
    var campoMenuTipoRol = document.getElementById('menu-tipo-rol');

    function cerrarMenuTipo() {
        modalMenuTipo.classList.remove('abierto');
    }

    function marcarCasillasMenuTipo() {
        var tipo = campoMenuTipoValor.value;
        var rolId = campoMenuTipoRol.value;
        var plantillasTipo = menuPorTipo[tipo] || {};
        var permitidos = (rolId && plantillasTipo[rolId]) ? plantillasTipo[rolId] : (plantillasTipo.general || []);

        modalMenuTipo.querySelectorAll('input[name="menu[]"]').forEach(function (casilla) {
            casilla.checked = permitidos.indexOf(casilla.value) !== -1;
        });
    }

    document.querySelectorAll('.boton-configurar-menu-tipo').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var tipo = boton.dataset.tipo;
            var rolesDelTipo = (rolesPorTipo[tipo] || []).map(String);

            campoMenuTipoNombre.textContent = tipo;
            campoMenuTipoValor.value = tipo;
            campoMenuTipoRol.value = '';

            Array.prototype.forEach.call(campoMenuTipoRol.options, function (opcion) {
                if (opcion.value === '') {
                    return;
                }

                opcion.hidden = rolesDelTipo.indexOf(opcion.value) === -1;
            });

            marcarCasillasMenuTipo();

            modalMenuTipo.classList.add('abierto');
        });
    });

    if (campoMenuTipoRol) {
        campoMenuTipoRol.addEventListener('change', marcarCasillasMenuTipo);
    }

    if (botonCerrarMenuTipo) {
        botonCerrarMenuTipo.addEventListener('click', cerrarMenuTipo);
    }

    modalMenuTipo.addEventListener('click', function (evento) {
        if (evento.target === modalMenuTipo) {
            cerrarMenuTipo();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarMenuTipo();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var checkboxesPendientes = document.querySelectorAll('.checkbox-pendiente');
    var checkboxPendientesTodos = document.getElementById('checkbox-pendientes-todos');

    if (checkboxesPendientes.length === 0 && !checkboxPendientesTodos) {
        return;
    }

    var barraAccionesPendientes = document.getElementById('barra-acciones-pendientes');
    var anioIdPendientes = barraAccionesPendientes ? barraAccionesPendientes.dataset.anioId : '';
    var bandejaPendientes = barraAccionesPendientes ? barraAccionesPendientes.dataset.bandeja : '';
    var modoPendientes = barraAccionesPendientes ? barraAccionesPendientes.dataset.modo : '';

    var botonPendientesAprobar = document.getElementById('boton-pendientes-aprobar');
    var botonPendientesArchivar = document.getElementById('boton-pendientes-archivar');

    function obtenerSeleccionadosPendientes() {
        return Array.prototype.filter.call(checkboxesPendientes, function (casilla) {
            return casilla.checked;
        });
    }

    function actualizarBotonesPendientes() {
        var seleccionados = obtenerSeleccionadosPendientes();
        var hay = seleccionados.length > 0;

        if (botonPendientesAprobar) {
            botonPendientesAprobar.disabled = !hay;
        }
        if (botonPendientesArchivar) {
            botonPendientesArchivar.disabled = !hay;
        }

        if (checkboxPendientesTodos) {
            checkboxPendientesTodos.checked = checkboxesPendientes.length > 0 && seleccionados.length === checkboxesPendientes.length;
        }
    }

    checkboxesPendientes.forEach(function (casilla) {
        casilla.addEventListener('change', actualizarBotonesPendientes);
    });

    if (checkboxPendientesTodos) {
        checkboxPendientesTodos.addEventListener('change', function () {
            checkboxesPendientes.forEach(function (casilla) {
                casilla.checked = checkboxPendientesTodos.checked;
            });
            actualizarBotonesPendientes();
        });
    }

    actualizarBotonesPendientes();

    function enviarFormularioPendientes(accion, seleccionados) {
        var formulario = document.createElement('form');
        formulario.method = 'POST';
        formulario.action = 'index.php?ruta=peticiones';
        formulario.style.display = 'none';

        function agregarCampo(nombre, valor) {
            var campo = document.createElement('input');
            campo.type = 'hidden';
            campo.name = nombre;
            campo.value = valor;
            formulario.appendChild(campo);
        }

        agregarCampo('accion', accion);
        agregarCampo('vista', 'pendientes');
        agregarCampo('anio_id', anioIdPendientes || '');
        agregarCampo('bandeja', bandejaPendientes || '');
        if (modoPendientes === 'jerarquia') {
            agregarCampo('modo', 'jerarquia');
        }

        seleccionados.forEach(function (casilla) {
            if (casilla.dataset.items) {
                var items = [];
                try {
                    items = JSON.parse(casilla.dataset.items);
                } catch (error) {
                    items = [];
                }
                items.forEach(function (item) {
                    agregarCampo('item_origen[]', item.origen || '');
                    agregarCampo('item_origen_id[]', item.origen_id || '');
                    agregarCampo('item_tipo[]', item.tipo || '');
                    agregarCampo('item_detalle[]', item.detalle || '');
                    agregarCampo('item_cantidad[]', item.cantidad || '');
                    agregarCampo('item_valor[]', item.valor || '');
                    agregarCampo('item_ruta_ver[]', item.ruta_ver || '');
                    agregarCampo('item_ruta_origen[]', item.ruta_origen || '');
                });
                return;
            }

            agregarCampo('item_origen[]', casilla.dataset.origen || '');
            agregarCampo('item_origen_id[]', casilla.dataset.origenId || '');
            agregarCampo('item_tipo[]', casilla.dataset.tipo || '');
            agregarCampo('item_detalle[]', casilla.dataset.detalle || '');
            agregarCampo('item_cantidad[]', casilla.dataset.cantidad || '');
            agregarCampo('item_valor[]', casilla.dataset.valor || '');
            agregarCampo('item_ruta_ver[]', casilla.dataset.rutaVer || '');
            agregarCampo('item_ruta_origen[]', casilla.dataset.rutaOrigen || '');
        });

        document.body.appendChild(formulario);
        formulario.submit();
    }

    if (botonPendientesAprobar) {
        botonPendientesAprobar.addEventListener('click', function () {
            if (botonPendientesAprobar.disabled) {
                return;
            }

            var seleccionados = obtenerSeleccionadosPendientes();

            if (!window.confirm('¿Aceptar ' + seleccionados.length + ' ítem(s) seleccionado(s)? Pasarán a "Consolidado por tipo".')) {
                return;
            }

            enviarFormularioPendientes('aprobar_pendientes_grupo', seleccionados);
        });
    }

    if (botonPendientesArchivar) {
        botonPendientesArchivar.addEventListener('click', function () {
            if (botonPendientesArchivar.disabled) {
                return;
            }

            var seleccionados = obtenerSeleccionadosPendientes();

            if (!window.confirm('¿Archivar ' + seleccionados.length + ' ítem(s) seleccionado(s)? Pasarán a "Archivados".')) {
                return;
            }

            enviarFormularioPendientes('archivar_pendientes_grupo', seleccionados);
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    var checkboxes = document.querySelectorAll('.checkbox-consolidado');
    var checkboxTodos = document.getElementById('checkbox-consolidado-todos');

    if (checkboxes.length === 0 && !checkboxTodos) {
        return;
    }

    var botonVer = document.getElementById('boton-consolidado-ver');
    var botonEditar = document.getElementById('boton-consolidado-editar');
    var botonRedireccionar = document.getElementById('boton-consolidado-redireccionar');
    var botonArchivar = document.getElementById('boton-consolidado-archivar');

    var barraAccionesConsolidado = document.getElementById('barra-acciones-consolidado');
    var anioIdConsolidado = barraAccionesConsolidado ? barraAccionesConsolidado.dataset.anioId : '';
    var bandejaConsolidado = barraAccionesConsolidado ? barraAccionesConsolidado.dataset.bandeja : '';

    var modalVerConsolidado = document.getElementById('modal-ver-consolidado');
    var modalEditarConsolidado = document.getElementById('modal-editar-consolidado');
    var modalRedireccionarConsolidado = document.getElementById('modal-redireccionar-consolidado');

    function obtenerSeleccionados() {
        return Array.prototype.filter.call(checkboxes, function (casilla) {
            return casilla.checked;
        });
    }

    function itemsDeSeleccion(seleccionados) {
        var items = [];
        seleccionados.forEach(function (casilla) {
            var propios = [];
            try {
                propios = JSON.parse(casilla.dataset.items || '[]');
            } catch (error) {
                propios = [];
            }
            items = items.concat(propios);
        });
        return items;
    }

    function tiposDeSeleccion(seleccionados) {
        var vistos = {};
        var tipos = [];
        seleccionados.forEach(function (casilla) {
            var tipo = casilla.dataset.tipo;
            if (tipo && !vistos[tipo]) {
                vistos[tipo] = true;
                tipos.push(tipo);
            }
        });
        return tipos;
    }

    function actualizarBotonesConsolidado() {
        var seleccionados = obtenerSeleccionados();
        var hay = seleccionados.length > 0;

        var todosEditables = hay && seleccionados.every(function (casilla) {
            return casilla.dataset.puedeEditar === '1' && casilla.dataset.redireccionado !== '1';
        });
        var ningunoRedireccionado = hay && seleccionados.every(function (casilla) {
            return casilla.dataset.redireccionado !== '1';
        });

        if (botonVer) {
            botonVer.disabled = !hay;
        }
        if (botonEditar) {
            botonEditar.disabled = !todosEditables;
        }
        if (botonRedireccionar) {
            botonRedireccionar.disabled = !ningunoRedireccionado;
        }
        if (botonArchivar) {
            botonArchivar.disabled = !ningunoRedireccionado;
        }

        if (checkboxTodos) {
            checkboxTodos.checked = checkboxes.length > 0 && seleccionados.length === checkboxes.length;
        }
    }

    checkboxes.forEach(function (casilla) {
        casilla.addEventListener('change', actualizarBotonesConsolidado);
    });

    if (checkboxTodos) {
        checkboxTodos.addEventListener('change', function () {
            checkboxes.forEach(function (casilla) {
                casilla.checked = checkboxTodos.checked;
            });
            actualizarBotonesConsolidado();
        });
    }

    actualizarBotonesConsolidado();

    // ---- Ver (uno o varios grupos a la vez) ----
    if (botonVer && modalVerConsolidado) {
        var botonCerrarVerConsolidado = document.getElementById('boton-cerrar-modal-ver-consolidado');
        var campoVerConsolidadoTipo = document.getElementById('ver-consolidado-tipo');
        var cuerpoVerConsolidado = document.getElementById('ver-consolidado-cuerpo');
        var enlaceVerConsolidadoCompleto = document.getElementById('enlace-ver-consolidado-completo');

        var cerrarVerConsolidado = function () {
            modalVerConsolidado.classList.remove('abierto');
        };

        botonVer.addEventListener('click', function () {
            if (botonVer.disabled) {
                return;
            }

            var seleccionados = obtenerSeleccionados();
            var items = itemsDeSeleccion(seleccionados);
            var tipos = tiposDeSeleccion(seleccionados);

            campoVerConsolidadoTipo.textContent = tipos.join(', ');
            cuerpoVerConsolidado.innerHTML = '';

            if (enlaceVerConsolidadoCompleto) {
                if (tipos.length === 1) {
                    enlaceVerConsolidadoCompleto.style.display = '';
                    enlaceVerConsolidadoCompleto.href = 'index.php?ruta=consolidado-detalle&tipo=' + encodeURIComponent(tipos[0])
                        + '&anio_id=' + encodeURIComponent(seleccionados[0].dataset.anioId || '');
                } else {
                    enlaceVerConsolidadoCompleto.style.display = 'none';
                }
            }

            if (items.length === 0) {
                var filaVacia = document.createElement('tr');
                filaVacia.innerHTML = '<td colspan="5">No hay elementos.</td>';
                cuerpoVerConsolidado.appendChild(filaVacia);
            }

            items.forEach(function (item) {
                var fila = document.createElement('tr');

                var celdaTipo = document.createElement('td');
                celdaTipo.textContent = item.tipo || '—';
                fila.appendChild(celdaTipo);

                var celdaDetalle = document.createElement('td');
                celdaDetalle.textContent = item.detalle || '—';
                fila.appendChild(celdaDetalle);

                var celdaCantidad = document.createElement('td');
                celdaCantidad.textContent = item.cantidad !== null && item.cantidad !== undefined && item.cantidad !== '' ? item.cantidad : '—';
                fila.appendChild(celdaCantidad);

                var celdaValor = document.createElement('td');
                celdaValor.textContent = item.valor !== null && item.valor !== undefined && item.valor !== ''
                    ? '$ ' + Number(item.valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : '—';
                fila.appendChild(celdaValor);

                var celdaAccion = document.createElement('td');
                var enlace = document.createElement('a');
                enlace.href = 'index.php?ruta=consolidado-detalle&tipo=' + encodeURIComponent(item.tipo || '')
                    + '&anio_id=' + encodeURIComponent(seleccionados[0] ? seleccionados[0].dataset.anioId || '' : '');
                enlace.className = 'boton-accion boton-accion-ver';
                enlace.textContent = 'Ver';
                celdaAccion.appendChild(enlace);

                var enlaceHistorial = document.createElement('a');
                enlaceHistorial.href = 'index.php?ruta=peticiones-historial-item&origen=' + encodeURIComponent(item.origen || '')
                    + '&origen_id=' + encodeURIComponent(item.origen_id || '')
                    + '&volver=' + encodeURIComponent(window.location.href);
                enlaceHistorial.className = 'boton-accion boton-accion-editar';
                enlaceHistorial.textContent = 'Historial';
                celdaAccion.appendChild(document.createTextNode(' '));
                celdaAccion.appendChild(enlaceHistorial);

                fila.appendChild(celdaAccion);

                cuerpoVerConsolidado.appendChild(fila);
            });

            if (items.length > 0) {
                var totalValor = 0;
                var hayValor = false;

                items.forEach(function (item) {
                    if (item.valor !== null && item.valor !== undefined && item.valor !== '') {
                        totalValor += Number(item.valor);
                        hayValor = true;
                    }
                });

                var filaTotal = document.createElement('tr');
                filaTotal.className = 'fila-total-consolidado';

                filaTotal.appendChild(document.createElement('td'));

                var celdaTotalEtiqueta = document.createElement('td');
                celdaTotalEtiqueta.innerHTML = '<strong>Total</strong>';
                filaTotal.appendChild(celdaTotalEtiqueta);

                filaTotal.appendChild(document.createElement('td'));

                var celdaTotalValor = document.createElement('td');
                celdaTotalValor.innerHTML = hayValor
                    ? '<strong>$ ' + totalValor.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</strong>'
                    : '—';
                filaTotal.appendChild(celdaTotalValor);

                filaTotal.appendChild(document.createElement('td'));

                cuerpoVerConsolidado.appendChild(filaTotal);
            }

            modalVerConsolidado.classList.add('abierto');
        });

        if (botonCerrarVerConsolidado) {
            botonCerrarVerConsolidado.addEventListener('click', cerrarVerConsolidado);
        }

        modalVerConsolidado.addEventListener('click', function (evento) {
            if (evento.target === modalVerConsolidado) {
                cerrarVerConsolidado();
            }
        });
    }

    // ---- Editar (uno o varios grupos a la vez): lista con un enlace "Editar" por ítem, que
    // navega al formulario real de su módulo de origen (con el ítem pre-cargado) y vuelve aquí. ----
    if (botonEditar && modalEditarConsolidado) {
        var botonCerrarEditarConsolidado = document.getElementById('boton-cerrar-modal-editar-consolidado');
        var campoEditarTipoTexto = document.getElementById('editar-consolidado-tipo-texto');
        var cuerpoEditarConsolidado = document.getElementById('editar-consolidado-cuerpo');

        var cerrarEditarConsolidado = function () {
            modalEditarConsolidado.classList.remove('abierto');
        };

        botonEditar.addEventListener('click', function () {
            if (botonEditar.disabled) {
                return;
            }

            var seleccionados = obtenerSeleccionados();
            var items = itemsDeSeleccion(seleccionados);
            var tipos = tiposDeSeleccion(seleccionados);
            var volver = encodeURIComponent(window.location.href);

            campoEditarTipoTexto.textContent = tipos.join(', ');
            cuerpoEditarConsolidado.innerHTML = '';

            if (items.length === 0) {
                var filaVacia = document.createElement('tr');
                filaVacia.innerHTML = '<td colspan="4">No hay elementos.</td>';
                cuerpoEditarConsolidado.appendChild(filaVacia);
            }

            items.forEach(function (item) {
                var fila = document.createElement('tr');

                var celdaTipo = document.createElement('td');
                celdaTipo.textContent = item.tipo || '—';
                fila.appendChild(celdaTipo);

                var celdaDetalle = document.createElement('td');
                celdaDetalle.textContent = item.dependencia || item.detalle || '—';
                fila.appendChild(celdaDetalle);

                var celdaValor = document.createElement('td');
                celdaValor.textContent = item.valor !== null && item.valor !== undefined && item.valor !== ''
                    ? '$ ' + Number(item.valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : '—';
                fila.appendChild(celdaValor);

                var celdaAccion = document.createElement('td');

                if (item.puede_editar) {
                    var mapaTab = {
                        gasto_extension: 'egresos', ingreso_extension: 'ingresos',
                        gasto_postgrado: 'egresos', ingreso_postgrado: 'ingresos',
                        gasto_unisalud: 'egresos', ingreso_unisalud: 'ingresos',
                        gasto_sin_excedentes: 'egresos', ingreso_sin_excedentes: 'ingresos',
                    };
                    var mapaTipoSolicitud = { arl: 'arl', monitores: 'monitores', ops: 'ops', otros: 'otros' };

                    var enlace = document.createElement('a');
                    var rutaOrigen = item.ruta_origen || 'index.php?ruta=peticiones';
                    var separador = rutaOrigen.indexOf('?') === -1 ? '?' : '&';
                    enlace.href = rutaOrigen + separador + 'editar_id=' + encodeURIComponent(item.origen_id)
                        + '&volver=' + volver
                        + (mapaTab[item.origen] ? '&tab=' + mapaTab[item.origen] : '')
                        + (mapaTipoSolicitud[item.origen] ? '&tipo_solicitud=' + mapaTipoSolicitud[item.origen] : '');
                    enlace.className = 'boton-accion boton-accion-editar';
                    enlace.textContent = 'Editar';
                    celdaAccion.appendChild(enlace);
                } else {
                    celdaAccion.textContent = '—';
                }

                fila.appendChild(celdaAccion);

                cuerpoEditarConsolidado.appendChild(fila);
            });

            modalEditarConsolidado.classList.add('abierto');
        });

        if (botonCerrarEditarConsolidado) {
            botonCerrarEditarConsolidado.addEventListener('click', cerrarEditarConsolidado);
        }

        modalEditarConsolidado.addEventListener('click', function (evento) {
            if (evento.target === modalEditarConsolidado) {
                cerrarEditarConsolidado();
            }
        });
    }

    // ---- Redireccionar (uno o varios grupos a la vez) ----
    if (botonRedireccionar && modalRedireccionarConsolidado) {
        var botonCerrarRedireccionarConsolidado = document.getElementById('boton-cerrar-modal-redireccionar-consolidado');
        var campoTipoTexto = document.getElementById('redireccionar-consolidado-tipo-texto');
        var contenedorCamposItemsRedireccionar = document.getElementById('redireccionar-consolidado-campos-items');
        var campoDependenciaRedireccionar = document.getElementById('redireccionar-consolidado-dependencia');
        var campoRolRedireccionar = document.getElementById('redireccionar-consolidado-rol');

        var cerrarRedireccionarConsolidado = function () {
            modalRedireccionarConsolidado.classList.remove('abierto');
        };

        botonRedireccionar.addEventListener('click', function () {
            if (botonRedireccionar.disabled) {
                return;
            }

            var seleccionados = obtenerSeleccionados();
            var items = itemsDeSeleccion(seleccionados);
            var tipos = tiposDeSeleccion(seleccionados);

            campoTipoTexto.textContent = '"' + tipos.join(', ') + '" (' + items.length + ' ítem(s))';
            contenedorCamposItemsRedireccionar.innerHTML = '';

            items.forEach(function (item) {
                var campoOrigen = document.createElement('input');
                campoOrigen.type = 'hidden';
                campoOrigen.name = 'item_origen[]';
                campoOrigen.value = item.origen || '';
                contenedorCamposItemsRedireccionar.appendChild(campoOrigen);

                var campoOrigenId = document.createElement('input');
                campoOrigenId.type = 'hidden';
                campoOrigenId.name = 'item_origen_id[]';
                campoOrigenId.value = item.origen_id || '';
                contenedorCamposItemsRedireccionar.appendChild(campoOrigenId);
            });

            campoDependenciaRedireccionar.value = '';
            campoRolRedireccionar.value = '';
            campoDependenciaRedireccionar.dispatchEvent(new Event('change'));
            campoRolRedireccionar.dispatchEvent(new Event('change'));

            if (window.aplicarFiltroRolUsuario) {
                window.aplicarFiltroRolUsuario(campoDependenciaRedireccionar);
            }

            modalRedireccionarConsolidado.classList.add('abierto');
            campoDependenciaRedireccionar.focus();
        });

        if (botonCerrarRedireccionarConsolidado) {
            botonCerrarRedireccionarConsolidado.addEventListener('click', cerrarRedireccionarConsolidado);
        }

        modalRedireccionarConsolidado.addEventListener('click', function (evento) {
            if (evento.target === modalRedireccionarConsolidado) {
                cerrarRedireccionarConsolidado();
            }
        });
    }

    // ---- Archivar (uno o varios grupos a la vez): re-archiva la misma fila con accion='archivada',
    // sin modal — no requiere elegir dependencia/rol. ----
    if (botonArchivar) {
        botonArchivar.addEventListener('click', function () {
            if (botonArchivar.disabled) {
                return;
            }

            var seleccionados = obtenerSeleccionados();
            var items = itemsDeSeleccion(seleccionados);

            if (!window.confirm('¿Archivar ' + items.length + ' ítem(s) seleccionado(s)? Pasarán a "Archivados" y dejarán de estar consolidados.')) {
                return;
            }

            var formulario = document.createElement('form');
            formulario.method = 'POST';
            formulario.action = 'index.php?ruta=peticiones';
            formulario.style.display = 'none';

            function agregarCampo(nombre, valor) {
                var campo = document.createElement('input');
                campo.type = 'hidden';
                campo.name = nombre;
                campo.value = valor;
                formulario.appendChild(campo);
            }

            agregarCampo('accion', 'archivar_consolidado');
            agregarCampo('vista', 'consolidado');
            agregarCampo('anio_id', anioIdConsolidado || '');
            agregarCampo('bandeja', bandejaConsolidado || '');

            items.forEach(function (item) {
                agregarCampo('item_origen[]', item.origen || '');
                agregarCampo('item_origen_id[]', item.origen_id || '');
            });

            document.body.appendChild(formulario);
            formulario.submit();
        });
    }

    document.addEventListener('keydown', function (evento) {
        if (evento.key !== 'Escape') {
            return;
        }
        [modalVerConsolidado, modalEditarConsolidado, modalRedireccionarConsolidado].forEach(function (modal) {
            if (modal) {
                modal.classList.remove('abierto');
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var checkboxesArchivado = document.querySelectorAll('.checkbox-archivado');
    var checkboxArchivadoTodos = document.getElementById('checkbox-archivado-todos');

    if (checkboxesArchivado.length === 0 && !checkboxArchivadoTodos) {
        return;
    }

    var barraAccionesArchivar = document.getElementById('barra-acciones-archivar');
    var anioIdArchivado = barraAccionesArchivar ? barraAccionesArchivar.dataset.anioId : '';
    var bandejaArchivado = barraAccionesArchivar ? barraAccionesArchivar.dataset.bandeja : '';

    var botonArchivadoVer = document.getElementById('boton-archivado-ver');
    var botonArchivadoDuplicar = document.getElementById('boton-archivado-duplicar');
    var botonArchivadoConsolidar = document.getElementById('boton-archivado-consolidar');
    var botonArchivadoEnviar = document.getElementById('boton-archivado-enviar');

    var modalVerArchivado = document.getElementById('modal-ver-archivado');
    var modalEnviarArchivado = document.getElementById('modal-enviar-archivado');

    function obtenerSeleccionadosArchivado() {
        return Array.prototype.filter.call(checkboxesArchivado, function (casilla) {
            return casilla.checked;
        });
    }

    function actualizarBotonesArchivado() {
        var seleccionados = obtenerSeleccionadosArchivado();
        var hay = seleccionados.length > 0;

        if (botonArchivadoVer) {
            botonArchivadoVer.disabled = !hay;
        }
        if (botonArchivadoDuplicar) {
            botonArchivadoDuplicar.disabled = !hay;
        }
        if (botonArchivadoConsolidar) {
            botonArchivadoConsolidar.disabled = !hay;
        }
        if (botonArchivadoEnviar) {
            botonArchivadoEnviar.disabled = !hay;
        }

        if (checkboxArchivadoTodos) {
            checkboxArchivadoTodos.checked = checkboxesArchivado.length > 0 && seleccionados.length === checkboxesArchivado.length;
        }
    }

    checkboxesArchivado.forEach(function (casilla) {
        casilla.addEventListener('change', actualizarBotonesArchivado);
    });

    if (checkboxArchivadoTodos) {
        checkboxArchivadoTodos.addEventListener('change', function () {
            checkboxesArchivado.forEach(function (casilla) {
                casilla.checked = checkboxArchivadoTodos.checked;
            });
            actualizarBotonesArchivado();
        });
    }

    actualizarBotonesArchivado();

    // ---- Ver (uno o varios ítems a la vez): igual que el Ver de Consolidado, pero sin JSON
    // agrupado — cada checkbox ya trae sus propios datos planos. ----
    if (botonArchivadoVer && modalVerArchivado) {
        var botonCerrarVerArchivado = document.getElementById('boton-cerrar-modal-ver-archivado');
        var cuerpoVerArchivado = document.getElementById('ver-archivado-cuerpo');

        var cerrarVerArchivado = function () {
            modalVerArchivado.classList.remove('abierto');
        };

        botonArchivadoVer.addEventListener('click', function () {
            if (botonArchivadoVer.disabled) {
                return;
            }

            var seleccionados = obtenerSeleccionadosArchivado();
            cuerpoVerArchivado.innerHTML = '';

            seleccionados.forEach(function (casilla) {
                var fila = document.createElement('tr');

                var celdaTipo = document.createElement('td');
                celdaTipo.textContent = casilla.dataset.tipo || '—';
                fila.appendChild(celdaTipo);

                var celdaDetalle = document.createElement('td');
                celdaDetalle.textContent = casilla.dataset.detalle || '—';
                fila.appendChild(celdaDetalle);

                var celdaCantidad = document.createElement('td');
                celdaCantidad.textContent = casilla.dataset.cantidad || '—';
                fila.appendChild(celdaCantidad);

                var celdaValor = document.createElement('td');
                celdaValor.textContent = casilla.dataset.valor
                    ? '$ ' + Number(casilla.dataset.valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : '—';
                fila.appendChild(celdaValor);

                var celdaAccion = document.createElement('td');
                if (casilla.dataset.rutaVer) {
                    var enlace = document.createElement('a');
                    enlace.href = casilla.dataset.rutaVer;
                    enlace.className = 'boton-accion boton-accion-ver';
                    enlace.textContent = 'Ver';
                    celdaAccion.appendChild(enlace);
                }
                fila.appendChild(celdaAccion);

                cuerpoVerArchivado.appendChild(fila);
            });

            modalVerArchivado.classList.add('abierto');
        });

        if (botonCerrarVerArchivado) {
            botonCerrarVerArchivado.addEventListener('click', cerrarVerArchivado);
        }

        modalVerArchivado.addEventListener('click', function (evento) {
            if (evento.target === modalVerArchivado) {
                cerrarVerArchivado();
            }
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape') {
                cerrarVerArchivado();
            }
        });
    }

    function enviarFormularioArchivado(accion, seleccionados) {
        var formulario = document.createElement('form');
        formulario.method = 'POST';
        formulario.action = 'index.php?ruta=peticiones';
        formulario.style.display = 'none';

        function agregarCampo(nombre, valor) {
            var campo = document.createElement('input');
            campo.type = 'hidden';
            campo.name = nombre;
            campo.value = valor;
            formulario.appendChild(campo);
        }

        agregarCampo('accion', accion);
        agregarCampo('vista', 'archivar');
        agregarCampo('anio_id', anioIdArchivado || '');
        agregarCampo('bandeja', bandejaArchivado || '');

        seleccionados.forEach(function (casilla) {
            agregarCampo('item_origen[]', casilla.dataset.origen || '');
            agregarCampo('item_origen_id[]', casilla.dataset.origenId || '');
        });

        document.body.appendChild(formulario);
        formulario.submit();
    }

    if (botonArchivadoDuplicar) {
        botonArchivadoDuplicar.addEventListener('click', function () {
            if (botonArchivadoDuplicar.disabled) {
                return;
            }

            var seleccionados = obtenerSeleccionadosArchivado();

            if (!window.confirm('¿Duplicar ' + seleccionados.length + ' ítem(s) seleccionado(s)? Se creará una copia de cada uno, también archivada.')) {
                return;
            }

            enviarFormularioArchivado('duplicar_archivado', seleccionados);
        });
    }

    if (botonArchivadoConsolidar) {
        botonArchivadoConsolidar.addEventListener('click', function () {
            if (botonArchivadoConsolidar.disabled) {
                return;
            }

            var seleccionados = obtenerSeleccionadosArchivado();

            if (!window.confirm('¿Consolidar ' + seleccionados.length + ' ítem(s) seleccionado(s)? Pasarán a "Consolidado por tipo" y dejarán de estar archivados.')) {
                return;
            }

            enviarFormularioArchivado('consolidar_archivado', seleccionados);
        });
    }

    if (botonArchivadoEnviar && modalEnviarArchivado) {
        var botonCerrarEnviarArchivado = document.getElementById('boton-cerrar-modal-enviar-archivado');
        var contenedorCamposItemsEnviarArchivado = document.getElementById('enviar-archivado-campos-items');
        var campoDependenciaEnviarArchivado = document.getElementById('enviar-archivado-dependencia');
        var campoRolEnviarArchivado = document.getElementById('enviar-archivado-rol');

        var cerrarEnviarArchivado = function () {
            modalEnviarArchivado.classList.remove('abierto');
        };

        botonArchivadoEnviar.addEventListener('click', function () {
            if (botonArchivadoEnviar.disabled) {
                return;
            }

            var seleccionados = obtenerSeleccionadosArchivado();
            contenedorCamposItemsEnviarArchivado.innerHTML = '';

            seleccionados.forEach(function (casilla) {
                var campoOrigen = document.createElement('input');
                campoOrigen.type = 'hidden';
                campoOrigen.name = 'item_origen[]';
                campoOrigen.value = casilla.dataset.origen || '';
                contenedorCamposItemsEnviarArchivado.appendChild(campoOrigen);

                var campoOrigenId = document.createElement('input');
                campoOrigenId.type = 'hidden';
                campoOrigenId.name = 'item_origen_id[]';
                campoOrigenId.value = casilla.dataset.origenId || '';
                contenedorCamposItemsEnviarArchivado.appendChild(campoOrigenId);
            });

            campoDependenciaEnviarArchivado.value = '';
            campoRolEnviarArchivado.value = '';
            campoDependenciaEnviarArchivado.dispatchEvent(new Event('change'));
            campoRolEnviarArchivado.dispatchEvent(new Event('change'));

            if (window.aplicarFiltroRolUsuario) {
                window.aplicarFiltroRolUsuario(campoDependenciaEnviarArchivado);
            }

            modalEnviarArchivado.classList.add('abierto');
            campoDependenciaEnviarArchivado.focus();
        });

        if (botonCerrarEnviarArchivado) {
            botonCerrarEnviarArchivado.addEventListener('click', cerrarEnviarArchivado);
        }

        modalEnviarArchivado.addEventListener('click', function (evento) {
            if (evento.target === modalEnviarArchivado) {
                cerrarEnviarArchivado();
            }
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape') {
                cerrarEnviarArchivado();
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    var checkboxesEnviado = document.querySelectorAll('.checkbox-enviado');
    var checkboxEnviadoTodos = document.getElementById('checkbox-enviado-todos');

    if (checkboxesEnviado.length === 0 && !checkboxEnviadoTodos) {
        return;
    }

    var barraAccionesEnviadas = document.getElementById('barra-acciones-enviadas');
    var anioIdEnviado = barraAccionesEnviadas ? barraAccionesEnviadas.dataset.anioId : '';
    var bandejaEnviado = barraAccionesEnviadas ? barraAccionesEnviadas.dataset.bandeja : '';

    var botonEnviadoVer = document.getElementById('boton-enviado-ver');
    var botonEnviadoDuplicar = document.getElementById('boton-enviado-duplicar');
    var botonEnviadoConsolidar = document.getElementById('boton-enviado-consolidar');
    var botonEnviadoEnviar = document.getElementById('boton-enviado-enviar');

    var modalVerEnviado = document.getElementById('modal-ver-enviado');
    var modalEnviarEnviado = document.getElementById('modal-enviar-enviado');

    function obtenerSeleccionadosEnviado() {
        return Array.prototype.filter.call(checkboxesEnviado, function (casilla) {
            return casilla.checked;
        });
    }

    function actualizarBotonesEnviado() {
        var seleccionados = obtenerSeleccionadosEnviado();
        var hay = seleccionados.length > 0;

        if (botonEnviadoVer) {
            botonEnviadoVer.disabled = !hay;
        }
        if (botonEnviadoDuplicar) {
            botonEnviadoDuplicar.disabled = !hay;
        }
        if (botonEnviadoConsolidar) {
            botonEnviadoConsolidar.disabled = !hay;
        }
        if (botonEnviadoEnviar) {
            botonEnviadoEnviar.disabled = !hay;
        }

        if (checkboxEnviadoTodos) {
            checkboxEnviadoTodos.checked = checkboxesEnviado.length > 0 && seleccionados.length === checkboxesEnviado.length;
        }
    }

    checkboxesEnviado.forEach(function (casilla) {
        casilla.addEventListener('change', actualizarBotonesEnviado);
    });

    if (checkboxEnviadoTodos) {
        checkboxEnviadoTodos.addEventListener('change', function () {
            checkboxesEnviado.forEach(function (casilla) {
                casilla.checked = checkboxEnviadoTodos.checked;
            });
            actualizarBotonesEnviado();
        });
    }

    actualizarBotonesEnviado();

    // ---- Ver (uno o varios ítems a la vez) ----
    if (botonEnviadoVer && modalVerEnviado) {
        var botonCerrarVerEnviado = document.getElementById('boton-cerrar-modal-ver-enviado');
        var cuerpoVerEnviado = document.getElementById('ver-enviado-cuerpo');

        var cerrarVerEnviado = function () {
            modalVerEnviado.classList.remove('abierto');
        };

        botonEnviadoVer.addEventListener('click', function () {
            if (botonEnviadoVer.disabled) {
                return;
            }

            var seleccionados = obtenerSeleccionadosEnviado();
            cuerpoVerEnviado.innerHTML = '';

            seleccionados.forEach(function (casilla) {
                var fila = document.createElement('tr');

                var celdaTipo = document.createElement('td');
                celdaTipo.textContent = casilla.dataset.tipo || '—';
                fila.appendChild(celdaTipo);

                var celdaDetalle = document.createElement('td');
                celdaDetalle.textContent = casilla.dataset.detalle || '—';
                fila.appendChild(celdaDetalle);

                var celdaCantidad = document.createElement('td');
                celdaCantidad.textContent = casilla.dataset.cantidad || '—';
                fila.appendChild(celdaCantidad);

                var celdaValor = document.createElement('td');
                celdaValor.textContent = casilla.dataset.valor
                    ? '$ ' + Number(casilla.dataset.valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : '—';
                fila.appendChild(celdaValor);

                var celdaAccion = document.createElement('td');
                if (casilla.dataset.rutaVer) {
                    var enlace = document.createElement('a');
                    enlace.href = casilla.dataset.rutaVer;
                    enlace.className = 'boton-accion boton-accion-ver';
                    enlace.textContent = 'Ver';
                    celdaAccion.appendChild(enlace);
                }
                fila.appendChild(celdaAccion);

                cuerpoVerEnviado.appendChild(fila);
            });

            modalVerEnviado.classList.add('abierto');
        });

        if (botonCerrarVerEnviado) {
            botonCerrarVerEnviado.addEventListener('click', cerrarVerEnviado);
        }

        modalVerEnviado.addEventListener('click', function (evento) {
            if (evento.target === modalVerEnviado) {
                cerrarVerEnviado();
            }
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape') {
                cerrarVerEnviado();
            }
        });
    }

    function enviarFormularioEnviado(accion, seleccionados) {
        var formulario = document.createElement('form');
        formulario.method = 'POST';
        formulario.action = 'index.php?ruta=peticiones';
        formulario.style.display = 'none';

        function agregarCampo(nombre, valor) {
            var campo = document.createElement('input');
            campo.type = 'hidden';
            campo.name = nombre;
            campo.value = valor;
            formulario.appendChild(campo);
        }

        agregarCampo('accion', accion);
        agregarCampo('vista', 'enviadas');
        agregarCampo('anio_id', anioIdEnviado || '');
        agregarCampo('bandeja', bandejaEnviado || '');

        seleccionados.forEach(function (casilla) {
            agregarCampo('item_origen[]', casilla.dataset.origen || '');
            agregarCampo('item_origen_id[]', casilla.dataset.origenId || '');
            agregarCampo('item_tipo[]', casilla.dataset.tipo || '');
            agregarCampo('item_detalle[]', casilla.dataset.detalle || '');
            agregarCampo('item_cantidad[]', casilla.dataset.cantidad || '');
            agregarCampo('item_valor[]', casilla.dataset.valor || '');
            agregarCampo('item_ruta_ver[]', casilla.dataset.rutaVer || '');
            agregarCampo('item_accion_actual[]', casilla.dataset.accionActual || '');
        });

        document.body.appendChild(formulario);
        formulario.submit();
    }

    if (botonEnviadoDuplicar) {
        botonEnviadoDuplicar.addEventListener('click', function () {
            if (botonEnviadoDuplicar.disabled) {
                return;
            }

            var seleccionados = obtenerSeleccionadosEnviado();

            if (!window.confirm('¿Duplicar ' + seleccionados.length + ' ítem(s) seleccionado(s)? Se creará una copia de cada uno, en el mismo estado que tiene hoy.')) {
                return;
            }

            enviarFormularioEnviado('duplicar_enviado', seleccionados);
        });
    }

    if (botonEnviadoConsolidar) {
        botonEnviadoConsolidar.addEventListener('click', function () {
            if (botonEnviadoConsolidar.disabled) {
                return;
            }

            var seleccionados = obtenerSeleccionadosEnviado();

            if (!window.confirm('¿Consolidar ' + seleccionados.length + ' ítem(s) seleccionado(s)? Pasarán a "Consolidado por tipo".')) {
                return;
            }

            enviarFormularioEnviado('consolidar_enviado', seleccionados);
        });
    }

    if (botonEnviadoEnviar && modalEnviarEnviado) {
        var botonCerrarEnviarEnviado = document.getElementById('boton-cerrar-modal-enviar-enviado');
        var contenedorCamposItemsEnviarEnviado = document.getElementById('enviar-enviado-campos-items');
        var campoDependenciaEnviarEnviado = document.getElementById('enviar-enviado-dependencia');
        var campoRolEnviarEnviado = document.getElementById('enviar-enviado-rol');

        var cerrarEnviarEnviado = function () {
            modalEnviarEnviado.classList.remove('abierto');
        };

        botonEnviadoEnviar.addEventListener('click', function () {
            if (botonEnviadoEnviar.disabled) {
                return;
            }

            var seleccionados = obtenerSeleccionadosEnviado();
            contenedorCamposItemsEnviarEnviado.innerHTML = '';

            function agregarCampoEnviar(nombre, valor) {
                var campo = document.createElement('input');
                campo.type = 'hidden';
                campo.name = nombre;
                campo.value = valor;
                contenedorCamposItemsEnviarEnviado.appendChild(campo);
            }

            seleccionados.forEach(function (casilla) {
                agregarCampoEnviar('item_origen[]', casilla.dataset.origen || '');
                agregarCampoEnviar('item_origen_id[]', casilla.dataset.origenId || '');
                agregarCampoEnviar('item_tipo[]', casilla.dataset.tipo || '');
                agregarCampoEnviar('item_detalle[]', casilla.dataset.detalle || '');
                agregarCampoEnviar('item_cantidad[]', casilla.dataset.cantidad || '');
                agregarCampoEnviar('item_valor[]', casilla.dataset.valor || '');
                agregarCampoEnviar('item_ruta_ver[]', casilla.dataset.rutaVer || '');
            });

            campoDependenciaEnviarEnviado.value = '';
            campoRolEnviarEnviado.value = '';
            campoDependenciaEnviarEnviado.dispatchEvent(new Event('change'));
            campoRolEnviarEnviado.dispatchEvent(new Event('change'));

            if (window.aplicarFiltroRolUsuario) {
                window.aplicarFiltroRolUsuario(campoDependenciaEnviarEnviado);
            }

            modalEnviarEnviado.classList.add('abierto');
            campoDependenciaEnviarEnviado.focus();
        });

        if (botonCerrarEnviarEnviado) {
            botonCerrarEnviarEnviado.addEventListener('click', cerrarEnviarEnviado);
        }

        modalEnviarEnviado.addEventListener('click', function (evento) {
            if (evento.target === modalEnviarEnviado) {
                cerrarEnviarEnviado();
            }
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape') {
                cerrarEnviarEnviado();
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    var modalEditarAnio = document.getElementById('modal-editar-anio');

    if (!modalEditarAnio) {
        return;
    }

    var botonCerrarEditarAnio = document.getElementById('boton-cerrar-modal-editar-anio');
    var campoAnioId = document.getElementById('editar-anio-id');
    var campoAnioAnio = document.getElementById('editar-anio-anio');
    var campoAnioPresupuesto = document.getElementById('editar-anio-presupuesto');

    function cerrarEditarAnio() {
        modalEditarAnio.classList.remove('abierto');
    }

    document.querySelectorAll('.boton-editar-anio').forEach(function (boton) {
        boton.addEventListener('click', function () {
            campoAnioId.value = boton.dataset.id;
            campoAnioAnio.value = boton.dataset.anio;
            campoAnioPresupuesto.value = boton.dataset.presupuesto;
            modalEditarAnio.classList.add('abierto');
        });
    });

    if (botonCerrarEditarAnio) {
        botonCerrarEditarAnio.addEventListener('click', cerrarEditarAnio);
    }

    modalEditarAnio.addEventListener('click', function (evento) {
        if (evento.target === modalEditarAnio) {
            cerrarEditarAnio();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarEditarAnio();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var contenedorFiltroFamilias = document.getElementById('filtro-familias-dependencia');

    if (!contenedorFiltroFamilias) {
        return;
    }

    var filas = document.querySelectorAll('.tabla-usuarios tbody tr[data-familia]');

    contenedorFiltroFamilias.querySelectorAll('.filtro-tag').forEach(function (tag) {
        tag.addEventListener('click', function () {
            contenedorFiltroFamilias.querySelectorAll('.filtro-tag').forEach(function (otro) {
                otro.classList.remove('activo');
            });
            tag.classList.add('activo');

            var familia = tag.dataset.familia;

            filas.forEach(function (fila) {
                fila.style.display = familia === '' || fila.dataset.familia === familia ? '' : 'none';
            });
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.acordeon-presupuestos').forEach(function (contenedor) {
        var grupos = Array.prototype.slice.call(contenedor.querySelectorAll('.acordeon-grupo'));

        function cerrarConAnimacion(grupo) {
            var cuerpo = grupo.querySelector('.acordeon-cuerpo');

            if (!grupo.open || !cuerpo) {
                grupo.open = false;
                return;
            }

            cuerpo.style.maxHeight = cuerpo.scrollHeight + 'px';

            window.requestAnimationFrame(function () {
                cuerpo.style.maxHeight = '0px';
            });

            cuerpo.addEventListener('transitionend', function manejador(evento) {
                if (evento.propertyName !== 'max-height') {
                    return;
                }

                cuerpo.removeEventListener('transitionend', manejador);
                grupo.open = false;
                cuerpo.style.maxHeight = '';
            }, { once: true });
        }

        function abrirConAnimacion(grupo) {
            var cuerpo = grupo.querySelector('.acordeon-cuerpo');

            grupo.open = true;

            if (!cuerpo) {
                return;
            }

            cuerpo.style.maxHeight = '0px';

            window.requestAnimationFrame(function () {
                cuerpo.style.maxHeight = cuerpo.scrollHeight + 'px';
            });

            cuerpo.addEventListener('transitionend', function manejador(evento) {
                if (evento.propertyName !== 'max-height') {
                    return;
                }

                cuerpo.removeEventListener('transitionend', manejador);
                cuerpo.style.maxHeight = '';
            }, { once: true });

            window.requestAnimationFrame(function () {
                grupo.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            });
        }

        window.establecerGrupoAcordeonAbierto = function (grupo, abierto) {
            if (abierto) {
                grupos.forEach(function (otro) {
                    if (otro !== grupo && otro.open) {
                        cerrarConAnimacion(otro);
                    }
                });
                abrirConAnimacion(grupo);
            } else {
                cerrarConAnimacion(grupo);
            }
        };

        grupos.forEach(function (grupo) {
            var resumen = grupo.querySelector('.acordeon-cabecera');

            if (!resumen) {
                return;
            }

            resumen.addEventListener('click', function (evento) {
                evento.preventDefault();
                window.establecerGrupoAcordeonAbierto(grupo, !grupo.open);
            });
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var buscador = document.getElementById('buscador-presupuestos-input');
    var contenedor = document.getElementById('acordeon-presupuestos');

    if (!buscador || !contenedor) {
        return;
    }

    var grupos = contenedor.querySelectorAll('.acordeon-grupo');
    var vacio = document.getElementById('buscador-presupuestos-vacio');

    buscador.addEventListener('input', function () {
        var texto = buscador.value.trim().toLowerCase();
        var algunGrupoVisible = false;
        var primerGrupoConCoincidencia = null;

        grupos.forEach(function (grupo) {
            var filas = grupo.querySelectorAll('.fila-presupuesto-dependencia');
            var algunaFilaCoincide = false;

            filas.forEach(function (fila) {
                var coincide = texto === '' || (fila.dataset.nombreBuscable || '').indexOf(texto) !== -1;
                fila.style.display = coincide ? '' : 'none';
                fila.classList.toggle('resaltada', coincide && texto !== '');

                if (coincide) {
                    algunaFilaCoincide = true;
                }
            });

            grupo.style.display = algunaFilaCoincide ? '' : 'none';

            if (algunaFilaCoincide) {
                algunGrupoVisible = true;

                if (texto !== '' && primerGrupoConCoincidencia === null) {
                    primerGrupoConCoincidencia = grupo;
                }
            }

            if (texto === '') {
                grupo.open = false;
            }
        });

        if (primerGrupoConCoincidencia !== null) {
            primerGrupoConCoincidencia.open = true;
        }

        if (vacio) {
            vacio.classList.toggle('visible', texto !== '' && !algunGrupoVisible);
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modalMensaje = document.getElementById('modal-mensaje');

    if (!modalMensaje) {
        return;
    }

    var botonAbrirMensaje = document.getElementById('boton-abrir-modal-mensaje');
    var botonCerrarMensaje = document.getElementById('boton-cerrar-modal-mensaje');

    function cerrarMensaje() {
        modalMensaje.classList.remove('abierto');
    }

    if (botonAbrirMensaje) {
        botonAbrirMensaje.addEventListener('click', function () {
            modalMensaje.classList.add('abierto');
        });
    }

    if (botonCerrarMensaje) {
        botonCerrarMensaje.addEventListener('click', cerrarMensaje);
    }

    modalMensaje.addEventListener('click', function (evento) {
        if (evento.target === modalMensaje) {
            cerrarMensaje();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarMensaje();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modalVerMensaje = document.getElementById('modal-ver-mensaje');

    if (!modalVerMensaje) {
        return;
    }

    var botonCerrarVerMensaje = document.getElementById('boton-cerrar-modal-ver-mensaje');
    var campoAsunto = document.getElementById('ver-mensaje-asunto');
    var campoRemitente = document.getElementById('ver-mensaje-remitente');
    var campoDestinatario = document.getElementById('ver-mensaje-destinatario');
    var campoFecha = document.getElementById('ver-mensaje-fecha');
    var campoCuerpo = document.getElementById('ver-mensaje-cuerpo');
    var botonResponderMensaje = document.getElementById('boton-responder-mensaje');

    function cerrarVerMensaje() {
        modalVerMensaje.classList.remove('abierto');
    }

    function marcarUiComoLeido(id) {
        document.querySelectorAll('.boton-ver-mensaje[data-id="' + id + '"]').forEach(function (boton) {
            boton.classList.remove('no-leido');
            boton.dataset.leido = '1';
        });

        var fila = document.querySelector('.fila-mensaje[data-mensaje-id="' + id + '"]');
        if (fila) {
            fila.classList.remove('fila-no-leida');
        }

        var insignia = document.querySelector('.insignia-no-leidos');
        if (insignia) {
            var restante = parseInt(insignia.textContent, 10) - 1;
            if (isNaN(restante) || restante <= 0) {
                insignia.remove();
            } else {
                insignia.textContent = restante > 9 ? '9+' : restante;
            }
        }
    }

    document.addEventListener('click', function (evento) {
        var boton = evento.target.closest('.boton-ver-mensaje');

        if (!boton) {
            return;
        }

        campoAsunto.textContent = boton.dataset.asunto;
        campoRemitente.textContent = boton.dataset.remitente;
        campoDestinatario.textContent = boton.dataset.destinatario;
        campoFecha.textContent = boton.dataset.fecha;
        campoCuerpo.textContent = boton.dataset.cuerpo;

        if (botonResponderMensaje) {
            botonResponderMensaje.href = 'index.php?ruta=mensajes&responder_a=' + encodeURIComponent(boton.dataset.contraparteId || '')
                + '&asunto=' + encodeURIComponent(boton.dataset.asunto || '');
        }

        modalVerMensaje.classList.add('abierto');

        if (boton.dataset.leido === '0') {
            var id = boton.dataset.id;

            fetch('index.php?ruta=mensajes-marcar-leido', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id),
            }).then(function () {
                marcarUiComoLeido(id);
            }).catch(function () {
                // Si falla la marca de lectura, el mensaje sigue mostrándose como no leído.
            });
        }
    });

    if (botonCerrarVerMensaje) {
        botonCerrarVerMensaje.addEventListener('click', cerrarVerMensaje);
    }

    modalVerMensaje.addEventListener('click', function (evento) {
        if (evento.target === modalVerMensaje) {
            cerrarVerMensaje();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarVerMensaje();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var botonesMarcarLeido = document.querySelectorAll('.boton-marcar-leido');

    if (botonesMarcarLeido.length === 0) {
        return;
    }

    botonesMarcarLeido.forEach(function (boton) {
        boton.addEventListener('click', function () {
            var id = boton.dataset.id;

            fetch('index.php?ruta=mensajes-marcar-leido', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id),
            }).then(function () {
                document.querySelectorAll('.boton-ver-mensaje[data-id="' + id + '"]').forEach(function (botonVer) {
                    botonVer.classList.remove('no-leido');
                    botonVer.dataset.leido = '1';
                });

                var fila = document.querySelector('.fila-mensaje[data-mensaje-id="' + id + '"]');
                if (fila) {
                    fila.classList.remove('fila-no-leida');
                }

                var insignia = document.querySelector('.insignia-no-leidos');
                if (insignia) {
                    var restante = parseInt(insignia.textContent, 10) - 1;
                    if (isNaN(restante) || restante <= 0) {
                        insignia.remove();
                    } else {
                        insignia.textContent = restante > 9 ? '9+' : restante;
                    }
                }

                boton.remove();
            }).catch(function () {
                // Si falla la marca de lectura, el botón sigue disponible para reintentar.
            });
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modalActa = document.getElementById('modal-acta');

    if (!modalActa) {
        return;
    }

    var botonAbrirActa = document.getElementById('boton-abrir-modal-acta');
    var botonCerrarActa = document.getElementById('boton-cerrar-modal-acta');

    function cerrarActa() {
        modalActa.classList.remove('abierto');
    }

    if (botonAbrirActa) {
        botonAbrirActa.addEventListener('click', function () {
            modalActa.classList.add('abierto');
        });
    }

    if (botonCerrarActa) {
        botonCerrarActa.addEventListener('click', cerrarActa);
    }

    modalActa.addEventListener('click', function (evento) {
        if (evento.target === modalActa) {
            cerrarActa();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarActa();
        }
    });

    // ---- Validación de tamaño + vista previa del PDF antes de enviar ----
    var TAMANO_MAXIMO_BYTES = 3 * 1024 * 1024;
    var campoArchivo = document.getElementById('archivo_acta');
    var campoArchivoError = document.getElementById('archivo_acta_error');
    var envoltorioVistaPrevia = document.getElementById('vista_previa_acta_envoltorio');
    var iframeVistaPrevia = document.getElementById('vista_previa_acta');
    var botonEnviarActa = document.getElementById('boton-enviar-acta');
    var urlVistaPreviaActual = null;

    if (campoArchivo) {
        campoArchivo.addEventListener('change', function () {
            if (urlVistaPreviaActual) {
                URL.revokeObjectURL(urlVistaPreviaActual);
                urlVistaPreviaActual = null;
            }

            campoArchivoError.style.display = 'none';
            campoArchivoError.textContent = '';
            envoltorioVistaPrevia.style.display = 'none';
            iframeVistaPrevia.src = '';
            botonEnviarActa.disabled = true;

            var archivo = campoArchivo.files && campoArchivo.files[0] ? campoArchivo.files[0] : null;

            if (!archivo) {
                return;
            }

            if (archivo.type !== 'application/pdf' && !/\.pdf$/i.test(archivo.name)) {
                campoArchivoError.textContent = 'Solo se permiten archivos en formato PDF.';
                campoArchivoError.style.display = '';
                campoArchivo.value = '';
                return;
            }

            if (archivo.size > TAMANO_MAXIMO_BYTES) {
                campoArchivoError.textContent = 'El archivo pesa ' + (archivo.size / (1024 * 1024)).toFixed(2) + ' MB. El máximo permitido es 3 MB.';
                campoArchivoError.style.display = '';
                campoArchivo.value = '';
                return;
            }

            urlVistaPreviaActual = URL.createObjectURL(archivo);
            iframeVistaPrevia.src = urlVistaPreviaActual;
            envoltorioVistaPrevia.style.display = '';
            botonEnviarActa.disabled = false;
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.boton-expandir-arbol').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var nodo = boton.closest('.nodo-arbol-presupuesto');

            if (!nodo) {
                return;
            }

            var subarbol = nodo.querySelector(':scope > .subarbol-presupuesto');

            if (!subarbol) {
                return;
            }

            var expandir = subarbol.classList.contains('oculto');
            subarbol.classList.toggle('oculto', !expandir);
            boton.textContent = expandir ? '−' : '+';
            boton.setAttribute('aria-expanded', expandir ? 'true' : 'false');
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modalVerVersion = document.getElementById('modal-ver-version');

    if (!modalVerVersion) {
        return;
    }

    var campoFecha = document.getElementById('ver-version-fecha');
    var campoUsuario = document.getElementById('ver-version-usuario');
    var contenedorDiff = document.getElementById('ver-version-diff');
    var campoIdRestaurar = document.getElementById('ver-version-id-restaurar');
    var campoIdNotificar = document.getElementById('ver-version-id-notificar');
    var botonCerrar = document.getElementById('boton-cerrar-modal-ver-version');
    var botonCerrarPie = document.getElementById('boton-cerrar-modal-ver-version-pie');

    function formatearMoneda(valor) {
        if (valor === null || valor === undefined) {
            return '—';
        }

        var numero = Number(valor);

        if (isNaN(numero)) {
            return '—';
        }

        return numero.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function cerrarVerVersion() {
        modalVerVersion.classList.remove('abierto');
    }

    document.querySelectorAll('.boton-ver-version').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var versionId = boton.dataset.versionId || '';
            var cambios = [];

            try {
                cambios = JSON.parse(boton.dataset.cambios || '[]');
            } catch (error) {
                cambios = [];
            }

            campoFecha.textContent = boton.dataset.fecha || '';
            campoUsuario.textContent = boton.dataset.usuario || '';
            campoIdRestaurar.value = versionId;
            campoIdNotificar.value = versionId;

            contenedorDiff.innerHTML = '';

            cambios.forEach(function (cambio) {
                var bloque = document.createElement('div');
                bloque.className = 'diff-dependencia';

                var nombre = document.createElement('p');
                nombre.className = 'diff-dependencia-nombre';
                nombre.textContent = cambio.dependencia_nombre;
                bloque.appendChild(nombre);

                var lineaAnterior = document.createElement('div');
                lineaAnterior.className = 'diff-linea diff-linea-quitado';
                lineaAnterior.innerHTML = '<span class="diff-signo">−</span>Mínimo: ' + formatearMoneda(cambio.minimo_anterior) + ' · Techo: ' + formatearMoneda(cambio.techo_anterior);
                bloque.appendChild(lineaAnterior);

                var lineaActual = document.createElement('div');
                lineaActual.className = 'diff-linea diff-linea-agregado';
                lineaActual.innerHTML = '<span class="diff-signo">+</span>Mínimo: ' + formatearMoneda(cambio.minimo_nuevo) + ' · Techo: ' + formatearMoneda(cambio.techo_nuevo);
                bloque.appendChild(lineaActual);

                contenedorDiff.appendChild(bloque);
            });

            modalVerVersion.classList.add('abierto');
        });
    });

    if (botonCerrar) {
        botonCerrar.addEventListener('click', cerrarVerVersion);
    }

    if (botonCerrarPie) {
        botonCerrarPie.addEventListener('click', cerrarVerVersion);
    }

    modalVerVersion.addEventListener('click', function (evento) {
        if (evento.target === modalVerVersion) {
            cerrarVerVersion();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarVerVersion();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var ICONO_CANDADO_CERRADO = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>';
    var ICONO_CANDADO_ABIERTO = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>';

    document.querySelectorAll('.boton-candado-techo').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var anioId = boton.dataset.anioId;
            var dependenciaId = boton.dataset.dependenciaId;

            boton.disabled = true;

            var datos = new URLSearchParams();
            datos.set('anio_id', anioId);
            datos.set('dependencia_id', dependenciaId);

            fetch('index.php?ruta=techos-alternar-bloqueo', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: datos.toString(),
            })
                .then(function (respuesta) { return respuesta.json(); })
                .then(function (datosRespuesta) {
                    if (!datosRespuesta.ok) {
                        return;
                    }

                    var bloqueado = datosRespuesta.bloqueado;
                    boton.dataset.bloqueado = bloqueado ? '1' : '0';
                    boton.classList.toggle('candado-cerrado', bloqueado);
                    boton.classList.toggle('candado-abierto', !bloqueado);
                    boton.innerHTML = bloqueado ? ICONO_CANDADO_CERRADO : ICONO_CANDADO_ABIERTO;
                    boton.title = bloqueado ? 'Bloqueado: clic para desbloquear' : 'Sin bloquear: clic para bloquear';

                    var fila = boton.closest('.fila-presupuesto-dependencia');
                    var campoTecho = fila ? fila.querySelector('input[name^="techo["]') : null;

                    if (campoTecho) {
                        campoTecho.disabled = false;
                    }
                })
                .finally(function () {
                    boton.disabled = false;
                });
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var camposConMinimo = [];

    document.querySelectorAll('.campo-techo-input').forEach(function (campo) {
        var minimoTexto = campo.dataset.minimo || '';
        var minimo = minimoTexto !== '' ? parseFloat(minimoTexto) : null;

        if (minimo === null || isNaN(minimo)) {
            return;
        }

        camposConMinimo.push({ campo: campo, minimo: minimo });

        var fila = campo.closest('.fila-presupuesto-dependencia');
        var advertencia = fila ? fila.querySelector('.advertencia-minimo') : null;

        if (!advertencia) {
            return;
        }

        function validar() {
            var valor = campo.value !== '' ? parseFloat(campo.value) : null;
            var esMenor = valor !== null && !isNaN(valor) && valor < minimo;
            advertencia.classList.toggle('oculto', !esMenor);
        }

        campo.addEventListener('input', validar);
        validar();
    });

    if (camposConMinimo.length === 0) {
        return;
    }

    var formulario = camposConMinimo[0].campo.closest('form');

    if (!formulario) {
        return;
    }

    formulario.addEventListener('submit', function (evento) {
        var invalido = camposConMinimo.find(function (item) {
            var valor = item.campo.value !== '' ? parseFloat(item.campo.value) : null;
            return valor !== null && !isNaN(valor) && valor < item.minimo;
        });

        if (invalido) {
            evento.preventDefault();
            alert('El techo presupuestal no puede ser menor al mínimo presupuestal de la dependencia.');
            invalido.campo.focus();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modalEditarVariable = document.getElementById('modal-editar-variable');

    if (!modalEditarVariable) {
        return;
    }

    var botonCerrarEditarVariable = document.getElementById('boton-cerrar-modal-editar-variable');

    function cerrarEditarVariable() {
        modalEditarVariable.classList.remove('abierto');
    }

    document.querySelectorAll('.boton-editar-variable').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var variable;

            try {
                variable = JSON.parse(boton.dataset.variable);
            } catch (error) {
                return;
            }

            document.getElementById('editar-variable-id').value = variable.id;
            document.getElementById('editar-variable-nombre').value = variable.nombre;
            document.getElementById('editar-variable-anio').value = variable.anio_presupuestal_id;
            document.getElementById('editar-variable-valor').value = variable.valor;

            modalEditarVariable.classList.add('abierto');
        });
    });

    if (botonCerrarEditarVariable) {
        botonCerrarEditarVariable.addEventListener('click', cerrarEditarVariable);
    }

    modalEditarVariable.addEventListener('click', function (evento) {
        if (evento.target === modalEditarVariable) {
            cerrarEditarVariable();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarEditarVariable();
        }
    });

    document.querySelectorAll('.form-eliminar-variable').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            if (!confirm('¿Eliminar esta variable macroeconómica? Esta acción no se puede deshacer.')) {
                evento.preventDefault();
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modalEditarItem = document.getElementById('modal-editar-autogestion-item');

    if (!modalEditarItem) {
        return;
    }

    var botonCerrarEditarItem = document.getElementById('boton-cerrar-modal-editar-autogestion-item');

    function cerrarEditarItem() {
        modalEditarItem.classList.remove('abierto');
    }

    document.querySelectorAll('.boton-editar-autogestion-item').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var item;

            try {
                item = JSON.parse(boton.dataset.item);
            } catch (error) {
                return;
            }

            document.getElementById('editar-autogestion-item-id').value = item.id;
            document.getElementById('editar-autogestion-item-nombre').value = item.nombre;

            modalEditarItem.classList.add('abierto');
        });
    });

    if (botonCerrarEditarItem) {
        botonCerrarEditarItem.addEventListener('click', cerrarEditarItem);
    }

    modalEditarItem.addEventListener('click', function (evento) {
        if (evento.target === modalEditarItem) {
            cerrarEditarItem();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarEditarItem();
        }
    });
});

/**
 * Botones "Enviar" de un solo clic (ARL/Monitores/OPS) que no tienen su propio selector de
 * dependencia+rol visible (ya quedaron fijos al crear la solicitud): antes de enviar, si hay más
 * de una persona con ese rol en esa dependencia, se intercepta el submit y se pide elegir a cuál
 * mediante un modal — luego se reenvía el mismo formulario ya con el destinatario elegido.
 */
document.addEventListener('DOMContentLoaded', function () {
    var datosUsuariosElemento = document.getElementById('datos-usuarios-por-dependencia-rol');
    var formulariosEnviar = document.querySelectorAll('.form-enviar-solicitud[data-dependencia]');
    var modal = document.getElementById('modal-elegir-destinatario');

    if (!datosUsuariosElemento || formulariosEnviar.length === 0 || !modal) {
        return;
    }

    var selectDestinatario = document.getElementById('elegir-destinatario-select');
    var botonConfirmar = document.getElementById('boton-confirmar-destinatario');
    var botonCerrar = document.getElementById('boton-cerrar-modal-elegir-destinatario');

    if (!selectDestinatario || !botonConfirmar) {
        return;
    }

    var usuariosPorDependenciaYRol = {};
    try {
        usuariosPorDependenciaYRol = JSON.parse(datosUsuariosElemento.textContent || '{}');
    } catch (error) {
        usuariosPorDependenciaYRol = {};
    }

    var formularioPendiente = null;

    function cerrarModal() {
        modal.classList.remove('abierto');
        formularioPendiente = null;
    }

    formulariosEnviar.forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            var dependencia = formulario.dataset.dependencia || '';
            var rol = formulario.dataset.rol || '';
            var candidatos = (dependencia && rol && usuariosPorDependenciaYRol[dependencia] && usuariosPorDependenciaYRol[dependencia][rol]) || [];

            if (candidatos.length <= 1) {
                return;
            }

            evento.preventDefault();

            selectDestinatario.innerHTML = '';
            candidatos.forEach(function (usuario) {
                var opcion = document.createElement('option');
                opcion.value = usuario.id;
                opcion.textContent = usuario.nombre;
                selectDestinatario.appendChild(opcion);
            });

            formularioPendiente = formulario;
            modal.classList.add('abierto');
        });
    });

    botonConfirmar.addEventListener('click', function () {
        if (!formularioPendiente) {
            return;
        }

        var campoDestinatario = formularioPendiente.querySelector('input[name="usuario_destinatario_id"]');
        if (campoDestinatario) {
            campoDestinatario.value = selectDestinatario.value;
        }

        var formularioAEnviar = formularioPendiente;
        cerrarModal();
        formularioAEnviar.submit();
    });

    if (botonCerrar) {
        botonCerrar.addEventListener('click', cerrarModal);
    }

    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) {
            cerrarModal();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarModal();
        }
    });
});


/**
 * Modal "Importar CSV" de los catálogos de Configuraciones (csv-configuracion.php): genérico,
 * solo hay uno por página. Confirma explícitamente antes de enviar, ya que reemplaza datos.
 */
document.addEventListener('DOMContentLoaded', function () {
    var botonAbrir = document.querySelector('.boton-abrir-modal-importar-csv');
    var modal = document.querySelector('[id^="modal-importar-csv-"]');

    if (!botonAbrir || !modal) {
        return;
    }

    var botonCerrar = modal.querySelector('.boton-cerrar-modal-importar-csv');
    var formulario = modal.querySelector('.form-confirmar-importar-csv');

    function abrirModal() {
        modal.classList.add('abierto');
    }

    function cerrarModal() {
        modal.classList.remove('abierto');
    }

    botonAbrir.addEventListener('click', abrirModal);

    if (botonCerrar) {
        botonCerrar.addEventListener('click', cerrarModal);
    }

    if (formulario) {
        formulario.addEventListener('submit', function (evento) {
            var mensaje = formulario.dataset.mensajeConfirmar || '¿Confirmas que quieres reemplazar los datos existentes con el contenido de este archivo?';

            if (!confirm(mensaje)) {
                evento.preventDefault();
            }
        });
    }

    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) {
            cerrarModal();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarModal();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var envolturas = document.querySelectorAll('.tabla-bulk-seleccionable');

    envolturas.forEach(function (envoltura) {
        var checkboxTodos = envoltura.querySelector('.checkbox-bulk-todos');
        var filas = envoltura.querySelectorAll('.checkbox-bulk-fila');
        var botonSeleccionar = document.getElementById(envoltura.dataset.botonSeleccionar || '');
        var botonEditar = document.getElementById(envoltura.dataset.botonEditar || '');
        var botonDuplicar = document.getElementById(envoltura.dataset.botonDuplicar || '');
        var botonEliminar = document.getElementById(envoltura.dataset.botonEliminar || '');

        function obtenerSeleccionadas() {
            return Array.prototype.filter.call(filas, function (casilla) { return casilla.checked; });
        }

        function actualizarBotones() {
            var seleccionadas = obtenerSeleccionadas();
            var hay = seleccionadas.length > 0;

            if (botonEditar) { botonEditar.disabled = seleccionadas.length !== 1; }
            if (botonDuplicar) { botonDuplicar.disabled = !hay; }
            if (botonEliminar) { botonEliminar.disabled = !hay; }

            if (checkboxTodos) {
                checkboxTodos.checked = filas.length > 0 && seleccionadas.length === filas.length;
            }
        }

        function limpiarSeleccion() {
            filas.forEach(function (casilla) { casilla.checked = false; });
            if (checkboxTodos) { checkboxTodos.checked = false; }
            actualizarBotones();
        }

        function enviarAccion(accion) {
            var seleccionadas = obtenerSeleccionadas();

            if (seleccionadas.length === 0) {
                return;
            }

            var mensaje = accion === 'eliminar'
                ? '¿Eliminar ' + seleccionadas.length + ' elemento(s) seleccionado(s)? Esta acción no se puede deshacer.'
                : '¿Duplicar ' + seleccionadas.length + ' elemento(s) seleccionado(s)?';

            if (!confirm(mensaje)) {
                return;
            }

            var accionValor = accion === 'eliminar' ? envoltura.dataset.accionEliminar : envoltura.dataset.accionDuplicar;

            if (!accionValor) {
                return;
            }

            var formulario = document.createElement('form');
            formulario.method = 'POST';
            formulario.action = envoltura.dataset.accionForm || window.location.href;
            formulario.style.display = 'none';

            function agregarCampo(nombre, valor) {
                var campo = document.createElement('input');
                campo.type = 'hidden';
                campo.name = nombre;
                campo.value = valor;
                formulario.appendChild(campo);
            }

            agregarCampo('accion', accionValor);

            if (envoltura.dataset.tab) {
                agregarCampo('tab', envoltura.dataset.tab);
            }

            seleccionadas.forEach(function (casilla) {
                agregarCampo('id[]', casilla.dataset.id);
            });

            document.body.appendChild(formulario);
            formulario.submit();
        }

        if (botonSeleccionar) {
            botonSeleccionar.addEventListener('click', function () {
                var activo = envoltura.classList.toggle('modo-seleccion');
                botonSeleccionar.setAttribute('aria-pressed', activo ? 'true' : 'false');

                if (!activo) {
                    limpiarSeleccion();
                }
            });
        }

        filas.forEach(function (casilla) {
            casilla.addEventListener('change', actualizarBotones);
            casilla.addEventListener('click', function (evento) { evento.stopPropagation(); });
        });

        if (checkboxTodos) {
            checkboxTodos.addEventListener('change', function () {
                filas.forEach(function (casilla) { casilla.checked = checkboxTodos.checked; });
                actualizarBotones();
            });
        }

        if (botonEditar) {
            botonEditar.addEventListener('click', function () {
                var seleccionadas = obtenerSeleccionadas();

                if (seleccionadas.length !== 1) {
                    return;
                }

                if (envoltura.dataset.editarEnPagina) {
                    var id = seleccionadas[0].dataset.id;
                    var base = envoltura.dataset.accionForm || window.location.href;
                    window.location.href = base + (base.indexOf('?') === -1 ? '?' : '&') + 'editar_id=' + encodeURIComponent(id);
                    return;
                }

                var fila = seleccionadas[0].closest('tr');
                var botonFila = fila ? fila.querySelector('.boton-editar-fila-generico') : null;

                if (botonFila) {
                    botonFila.click();
                }
            });
        }

        if (botonDuplicar) {
            botonDuplicar.addEventListener('click', function () { enviarAccion('duplicar'); });
        }

        if (botonEliminar) {
            botonEliminar.addEventListener('click', function () { enviarAccion('eliminar'); });
        }

        actualizarBotones();
    });
});

/**
 * Ordenamiento de tablas al hacer clic en el encabezado: cualquier <th class="th-ordenable"> ordena
 * las filas de su <tbody> por esa columna. Si la celda trae data-orden, se compara como número
 * (columnas de cantidad/valor); si no, se compara el texto visible (columnas de texto). El primer
 * clic ordena de mayor a menor; un segundo clic sobre el mismo encabezado invierte a menor a mayor.
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('th.th-ordenable').forEach(function (encabezado) {
        encabezado.addEventListener('click', function () {
            var fila = encabezado.parentElement;
            var tabla = encabezado.closest('table');
            var cuerpo = tabla ? tabla.querySelector('tbody') : null;

            if (!fila || !cuerpo) {
                return;
            }

            var encabezados = Array.prototype.slice.call(fila.children);
            var indice = encabezados.indexOf(encabezado);
            var nuevaDireccion = encabezado.classList.contains('orden-desc') ? 'asc' : 'desc';

            encabezados.forEach(function (th) {
                th.classList.remove('orden-asc', 'orden-desc');
            });
            encabezado.classList.add(nuevaDireccion === 'asc' ? 'orden-asc' : 'orden-desc');

            var filas = Array.prototype.slice.call(cuerpo.querySelectorAll(':scope > tr'));

            filas.sort(function (filaA, filaB) {
                var celdaA = filaA.children[indice];
                var celdaB = filaB.children[indice];

                if (!celdaA || !celdaB) {
                    return 0;
                }

                var esNumero = celdaA.dataset.orden !== undefined;
                var valorA = esNumero ? parseFloat(celdaA.dataset.orden) : celdaA.textContent.trim().toLowerCase();
                var valorB = esNumero ? parseFloat(celdaB.dataset.orden) : celdaB.textContent.trim().toLowerCase();

                if (valorA < valorB) {
                    return nuevaDireccion === 'asc' ? -1 : 1;
                }
                if (valorA > valorB) {
                    return nuevaDireccion === 'asc' ? 1 : -1;
                }
                return 0;
            });

            filas.forEach(function (fila) {
                cuerpo.appendChild(fila);
            });
        });
    });
});
