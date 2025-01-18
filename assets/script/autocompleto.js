//#region AUTOCOMPLETE_CLIENTES
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('busqueda');
    const suggestionsPanel = document.getElementById('suggestions');
    const codClienteInput = document.getElementById('codcliente');

    searchInput.addEventListener('input', function () {
        const searchQuery = this.value;
        if (searchQuery) {
            fetch(`autocomplete_cliente.php?q=${searchQuery}`)
                .then(response => response.json())
                .then(data => {
                    suggestionsPanel.innerHTML = '';
                    data.forEach(item => {
                        const suggestion = document.createElement('div');
                        const clientInfo = `
                        <strong>${item.nombre}</strong><br>
                    `;
                        suggestion.innerHTML = clientInfo;
                        suggestion.addEventListener('click', () => {
                            // Al hacer clic en una sugerencia, actualizar los campos y ocultar las sugerencias
                            codClienteInput.value = item.idcliente; // Asumiendo que el objeto item tiene un campo 'id'
                            searchInput.value = `${item.nombre}`; // Puedes ajustar esto según tus necesidades
                            suggestionsPanel.innerHTML = '';
                            suggestionsPanel.style.display = 'none';
                        });
                        suggestionsPanel.appendChild(suggestion);
                    });
                    suggestionsPanel.style.display = 'block';
                })
                .catch(error => console.error('Error:', error));
        } else {
            suggestionsPanel.innerHTML = '';
            suggestionsPanel.style.display = 'none';
        }
    });

    document.addEventListener('click', function (event) {
        if (!searchInput.contains(event.target) && !suggestionsPanel.contains(event.target)) {
            suggestionsPanel.style.display = 'none';
        }
    });
});
//#endregion

//#region AUTOCOMPLETE_PRODUCTOS
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('busquedaproductov');
    const suggestionsPanel = document.getElementById('sugerencia');
    const codProductoInput = document.getElementById('codproducto');
    let carrito = [];

    searchInput.addEventListener('input', function () {
        const searchQuery = this.value;
        if (searchQuery) {
            fetch(`autocomplete_producto.php?q=${searchQuery}`)
                .then(response => response.json())
                .then(data => {
                    suggestionsPanel.innerHTML = '';
                    data.forEach(item => {
                        const suggestion = document.createElement('div');
                        const productInfo = `
                            <strong>${item.descripcion}</strong><br>
                        `;
                        suggestion.innerHTML = productInfo;
                        suggestion.addEventListener('click', () => {
                            agregarAlCarrito(item);
                            suggestionsPanel.innerHTML = '';
                            suggestionsPanel.style.display = 'none';
                        });
                        suggestionsPanel.appendChild(suggestion);
                    });
                    suggestionsPanel.style.display = 'block';
                })
                .catch(error => console.error('Error:', error));
        } else {
            suggestionsPanel.innerHTML = '';
            suggestionsPanel.style.display = 'none';
        }

    });

    document.addEventListener('click', function (event) {
        if (!searchInput.contains(event.target) && !suggestionsPanel.contains(event.target)) {
            suggestionsPanel.style.display = 'none';
        }
    });

    function agregarAlCarrito(producto) {
        const index = carrito.findIndex(item => item.codproducto === producto.codproducto)
        if (index !== -1) {
            carrito[index].cantidad++;
        } else {
            producto.cantidad = 1;
            carrito.push(producto);
        }
        actualizarCarrito();
    }

    function actualizarCarrito() {
        const tbody = document.querySelector('#carrito tbody');
        let subtotal = 0;

        tbody.innerHTML = '';
        carrito.forEach(producto => {
            const totalProducto = producto.precio * producto.cantidad;
            subtotal += totalProducto;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="cantidad-control">
                    <button class="menos-btn btn btn-primary" data-id="${producto.codproducto}"><i class="fa fa-minus"></i></button>
                    <span><input  value="${producto.cantidad}" style='width:45px;height:27px;border:#000;' class="cantidad-input" data-id="${producto.codproducto}"/></span>
                    <button class="mas-btn btn btn-primary" data-id="${producto.codproducto}"><i class="fa fa-plus"></i></button>
                </td>
                <td hidden>${producto.codBarra}</td>
                <td>${producto.descripcion}</td>
                <td class="centrarcelda">${producto.precio}</td>
                <td class="centrarcelda">${totalProducto.toFixed(2)}</td>
                <td class="centrarcelda"><button class="btn btn-danger remove-btn" data-id="${producto.codproducto}"><i class="fa fa-trash"></i></button></td>
            `;
            tbody.appendChild(tr);
        });

        const removeButtons = document.querySelectorAll('.remove-btn');
        removeButtons.forEach(button => {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                removerDelCarrito(id);
            });
        });

        const masButtons = document.querySelectorAll('.mas-btn');
        masButtons.forEach(button => {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                incrementarCantidad(id);
            });
        });

        const menosButtons = document.querySelectorAll('.menos-btn');
        menosButtons.forEach(button => {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                decrementarCantidad(id);
            });
        });

        document.querySelectorAll('.cantidad-input').forEach(input => {
            input.addEventListener('change', function () {
                const id = this.getAttribute('data-id');
                const nuevaCantidad = this.value;
                cambiarCantidad(id, nuevaCantidad);
            });
        });

        // Calcular y mostrar el subtotal, el impuesto y el total de la venta
        const subtotalV = subtotal / 1.13;
        const ImpuestoV = subtotalV * 0.13;
        const TotalVentaV = subtotalV + ImpuestoV;

        $("#subtotal").text(subtotalV.toFixed(2));
        $("#iva_impuesto").text(ImpuestoV.toFixed(2));
        $("#totalpagar").text(TotalVentaV.toFixed(2));

        $("#buttonpago").attr('disabled', false);
    }

    function removerDelCarrito(id) {
        const index = carrito.findIndex(item => item.codproducto == id);
        if (index !== -1) {
            carrito.splice(index, 1);
        }
        actualizarCarrito();
    }

    function incrementarCantidad(id) {
        const index = carrito.findIndex(item => item.codproducto == id);
        if (index !== -1) {
            carrito[index].cantidad++;
        }
        actualizarCarrito();
    }

    function decrementarCantidad(id) {
        const index = carrito.findIndex(item => item.codproducto == id);
        if (index !== -1 && carrito[index].cantidad > 1) {
            carrito[index].cantidad--;
        }
        actualizarCarrito();
    }

    function cambiarCantidad(id, nuevaCantidad) {
        const index = carrito.findIndex(item => item.codproducto == id);
        if (index !== -1 && nuevaCantidad > 0) {
            carrito[index].cantidad = nuevaCantidad;
            actualizarCarrito();
        }
    }

    // Evento para vaciar el carrito
    document.getElementById('vaciar').addEventListener('click', function () {
        carrito.length = 0; // Vaciar el array
        actualizarCarrito(); // Actualizar la tabla
        window.location.href = 'index.php?page=nueva_venta';
    });

    document.getElementById('saveventa').addEventListener('submit', function (event) {
        const busqueda = document.getElementById('busqueda').value.trim();
        const prefix = document.getElementById('prefix').value.trim();


        if (busqueda === "" || prefix === "") {
            event.preventDefault();
            Swal.fire({

                title: 'Error!',

                text: 'El campo busqueda cliente y comporbante son obligatorios.',

                icon: 'danger',

                confirmButtonColor: '#ad0000',

                confirmButtonText: 'OK'

            }).then((result) => {

                if (result.isConfirmed) {

                    location.reload();

                }

            });
        }
        else {
            event.preventDefault();
            start_load();
            // const csrfToken = '<?php echo $csrf_token; ?>';
            // sessionStorage.setItem('csrf_token', csrfToken);
            const carrito = [];
            $('#carrito tbody tr').each(function () {
                const cantidad = $(this).find('td').eq(0).find('input').val();
                const codproducto = $(this).find('td').eq(1).text();
                const precio = $(this).find('td').eq(3).text();


                carrito.push({
                    codproducto: codproducto,
                    precio: parseFloat(precio),
                    cantidad: parseInt(cantidad)
                });
            });

            let subtotalV = 0;
            carrito.forEach(producto => {
                subtotalV += producto.precio * producto.cantidad;
            });

            let ImpuestoV = subtotalV * 0.13;

            const data = {
                subtotal: $("#subtotal").text(),
                iva_impuesto: $("#iva_impuesto").text(),
                totalpagar: $("#totalpagar").text(),
                codcliente: $("#codcliente").val(),
                prefix: $("#prefix").val(),
                csrf_token: $("#csrf_token").val(),
                detalle: JSON.stringify(carrito)
            };

            $.ajax({
                url: 'ajax.php?action=save_venta_previa',
                method: 'POST',
                data: data,
                success: function (resp) {
                    let response = JSON.parse(resp);
                    let idfactura = response.idfactura;
                    if (response.success) {
                        
                        uni_modal_generador("Cobrar venta", "ventas.php?idfactura="+idfactura);
                        end_load()
                    }
                    else {

                    }

                }
            });
        }
    });
});



//#endregion