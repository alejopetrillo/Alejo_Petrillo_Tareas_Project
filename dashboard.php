
<?php
require_once __DIR__ . '/helpers.php';

$usuario_actual = exigir_login($conexion);


if ($usuario_actual['rol'] === 'admin') {
    redirigir('admin_users.php');
}

$titulo_pagina = 'Patitas Felices - Adopciones';
require __DIR__ . '/header.php';
?>

<main class="contenedor">

    <section class="portada-perrera">

        <div class="portada-contenido">

            <span class="etiqueta">REFUGIO DE ANIMALES</span>

            <h1>Patitas Felices</h1>

            <p>
                Cada perro merece una familia que lo quiera.
                Ayudanos a encontrar un hogar para aquellos
                que más lo necesitan.
            </p>

            <a href="#perros" class="btn">
                Conocer a los perros
            </a>

        </div>

    </section>

    <?php mostrar_flash(); ?>

    
    <section class="bienvenida">

        <h2>
            ¡Hola, <?= escapar($usuario_actual['nombre']) ?>!
        </h2>

        <p>
            Bienvenido a nuestra comunidad de adopción.
            Nos alegra que quieras conocer a nuestros perros.
        </p>

    </section>

  
    <section class="tarjeta">

        <h2>Mis datos</h2>

        <dl class="datos">

            <dt>Nombre</dt>
            <dd><?= escapar($usuario_actual['nombre']) ?></dd>

            <dt>Apellido</dt>
            <dd><?= escapar($usuario_actual['apellido']) ?></dd>

            <dt>DNI</dt>
            <dd><?= escapar($usuario_actual['dni']) ?></dd>

            <dt>Correo</dt>
            <dd><?= escapar($usuario_actual['email']) ?></dd>

            <dt>Tipo de cuenta</dt>
            <dd>
                <span class="badge">
                    <?= escapar($usuario_actual['rol']) ?>
                </span>
            </dd>

            <dt>Miembro desde</dt>
            <dd>
                <?= escapar(date('d/m/Y', strtotime($usuario_actual['creado_en']))) ?>
            </dd>

        </dl>

    </section>

   
    <section id="perros" class="seccion-perros">

        <div class="titulo-seccion">

            <span>NUESTROS ANIMALES</span>

            <h2>Encontrá a tu nuevo mejor amigo</h2>

            <p>
                Estos son algunos de los perros que esperan
                encontrar una familia.
            </p>

        </div>

        <div class="perros-grid">

           
            <article class="perro-card">

                <img src="img/max.jpg" alt="Max">

                <div class="perro-info">

                    <span class="estado-perro">Disponible</span>

                    <h3>Max</h3>

                    <p class="edad-perro">2 años | Macho</p>

                    <p>
                        Es un perro cariñoso, juguetón y muy amigable.
                        Le encanta salir a pasear.
                    </p>

                    <a href="#contacto" class="btn btn-chico">
                        Quiero adoptarlo
                    </a>

                </div>

            </article>

           
            <article class="perro-card">

                <img src="img/luna.jpg"alt="Luna">

                <div class="perro-info">

                    <span class="estado-perro">Disponible</span>

                    <h3>Luna</h3>

                    <p class="edad-perro">1 año | Hembra</p>

                    <p>
                        Es tranquila, dulce y disfruta mucho
                        de la compañía de las personas.
                    </p>

                    <a href="#contacto" class="btn btn-chico">
                        Quiero adoptarla
                    </a>

                </div>

            </article>

      
            <article class="perro-card">

                <img src="img/toby.jpg" alt="Toby">

                <div class="perro-info">

                    <span class="estado-perro">Disponible</span>

                    <h3>Toby</h3>

                    <p class="edad-perro">3 años | Macho</p>

                    <p>
                        Es muy compañero, inteligente y le gusta
                        jugar con otros perros.
                    </p>

                    <a href="#contacto" class="btn btn-chico">
                        Quiero adoptarlo
                    </a>

                </div>

            </article>

        </div>

    </section>

    
    <section class="adopcion-info">

        <h2>¿Por qué adoptar?</h2>

        <p>
            Adoptar significa darle una segunda oportunidad a un animal
            que necesita cariño, protección y una familia.
            No solamente cambiás su vida, también sumás un compañero
            para la tuya.
        </p>

        <div class="beneficios-grid">

            <div class="beneficio">

                <h3>Amor incondicional</h3>

                <p>
                    Vas a encontrar un compañero que te dará
                    cariño todos los días, además te acompañara siempre que lo necesites.
                </p>

            </div>

            <div class="beneficio">

                <h3>Un nuevo hogar</h3>

                <p>
                    Le brindás la oportunidad de crecer
                    en una familia, que lo quiere, amá y se preocupa por él.
                </p>

            </div>

            <div class="beneficio">

                <h3>Una nueva oportunidad</h3>

                <p>
                    Ayudás a un animal que necesita
                    protección, compañia y un dueño que al que querer .
                </p>

            </div>

        </div>

    </section>

   
    <section id="contacto" class="contacto-perrera">

        <h2>¿Querés adoptar?</h2>

        <p>
            Si alguno de nuestros perros te llamó la atención,
            acercate a nuestra perrera para conocerlo y comenzar
            el proceso de adopción.
        </p>

        <p class="contacto-frase">
            Tu próximo mejor amigo puede estar esperándote.
        </p>

    </section>

</main>



