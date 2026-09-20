<button class="menu-toggle" onclick="toggleMenu()">
☰ Menú
</button>

<nav class="menu" id="menu">

<a href="index.php">Dashboard</a>
<a href="productos.php">Productos</a>
<a href="clientes.php">Clientes</a>
<a href="ventas.php">Ventas</a>
<a href="compras.php">Compras</a>
<a href="historial_ventas.php">Historial de Ventas</a>
<a href="historial_compras.php">Historial de Compras</a>
<a href="reportes.php">Reportes</a>
<?php if(isset($_SESSION['rol']) && $_SESSION['rol'] === 'Administrador'){ ?>

<a href="lista_respaldos.php">Respaldos</a>
<a href="usuarios.php">Usuarios</a>

<?php } ?>
<a href="perfil.php">Mi Perfil</a>
<a href="logout.php">Cerrar Sesión</a>

</nav>

<script>

function toggleMenu(){

    document
    .getElementById("menu")
    .classList
    .toggle("mostrar");

}

</script>