let cart = [];
const cartItems = document.getElementById('cart-items');
const subtotalElement = document.getElementById('subtotal');
const igvElement = document.getElementById('igv');
const totalElement = document.getElementById('total');

function addToCart(id, name, price) {
    const existingProduct = cart.find(item => item.id === id);

    if (existingProduct) {
        existingProduct.quantity++;
    } else {
        cart.push({ id, name, price, quantity: 1 });
    }
    renderCart();
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    renderCart();
}

function renderCart() {
    cartItems.innerHTML = '';
    let subtotal = 0;

    cart.forEach((item, index) => {
        const total = item.price * item.quantity;
        subtotal += total;

        cartItems.innerHTML += `
            <tr>
                <td>${index + 1}</td>
                <td>${item.name}</td>
                <td>
                    <div class="input-group" style="max-width: 115px;">
                        <div class="input-group-prepend" style="cursor: pointer;" onclick="updateQuantity(${item.id}, -1)">
                            <span class="input-group-text">-</span>
                        </div>
                            <input type="text" disabled class="form-control" style="background:white;" aria-label="" value=" ${item.quantity}">
                        <div class="input-group-append" style="cursor: pointer;" onclick="updateQuantity(${item.id}, 1)">
                            <span class="input-group-text">+</span>
                        </div>
                    </div>
                </td>
                <td>$ ${item.price.toFixed(2)}</td>
                <td>$ ${total.toFixed(2)}</td>
                <td><button class="btn btn-danger" onclick="removeFromCart(${item.id})">X</button></td>
            </tr>
        `;
    });

    const sbTotal = subtotal / 1.13;
    const igv = sbTotal * 0.13;
    const total = sbTotal + igv;

    subtotalElement.textContent = `$ ${sbTotal.toFixed(2)}`;
    igvElement.textContent = `$ ${igv.toFixed(2)}`;
    totalElement.textContent = `$ ${total.toFixed(2)}`;
}

function updateQuantity(id, change) {
    const product = cart.find(item => item.id === id);
    if (product) {
        product.quantity += change;
        if (product.quantity <= 0) {
            removeFromCart(id);
        } else {
            renderCart();
        }
    }
}

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

