<?php

/**
 * Template Name: Marca
 *
 * @package WordPress
 */

get_header();

?>
  <main class="woowContentFull main main_marca">
    <div class="woowContent1400">
      <div class="home_banner">
        <a href=""><img src="<?= IMGURL ?>marca/marca.png" alt=""></a>
      </div>
      <div class="content_info_marca">
        <h1>It's fun to be kinky</h1>
        <h2><?= __('A sólo dos años de entrar en el mercado colombiano, Dale Más se posiciona como la principal marca que responde a las necesidades de una creciente comunidad fetish y kinky en el país.', 'Dale') ?></h2>
        <p class="title_marca">INTERNATIONAL</p>
        <p class="description_marca"><?= __('La idea nace en Ámsterdam, Holanda, como resultado del deseo de Dayner y su pareja de regresar a Colombia luego de 20 años de vivir en el exterior y de compartir con una comunidad fetish, que cada día gana más adeptos alrededor del mundo y que en Colombia se ha ido popularizando a través de las diferentes fiestas temáticas, los encuentros de grupos leather y BDSM y la cotidianidad de quienes ven en estas prendas, una forma de expresar su estilo de vida.', 'Dale') ?></p>
        <p class="title_marca"><?= __('CALIDAD', 'Dale') ?></p>
        <p class="description_marca"><?= __('Después de iniciar con un pequeño punto de venta en Chapinero-Bogotá, Dale Más ha encontrado una gran oportunidad para la comercialización de sus productos en diferentes ciudades del país, resaltando sus altos estándares de calidad europeos y en materiales como el cuero y el neopreno. La marca se ha convertido en una motivación para el fortalecimiento de los diferentes colectivos leather y BDSM, brindando la oportunidad de ser “mucho más abiertos y orgullosos de lo que disfrutan como comunidad”, afirma Dayner.', 'Dale') ?></p>
        <p class="title_marca">ITS FUN TO BE KINKY</p>
        <p class="description_marca"><?= __('Luego de nuestro primer año en el mercado, hemos comenzado un proceso de crecimiento, generando nuevas alianzas que nos han permitido llegar con nuestros productos a un mayor número de clientes, los cuales exploran abiertamente su sexualidad. Es por ello que con el slogan “it’s fun to be kinky”, la marca busca desestigmatizar el uso del cuero y las prendas fetish, para convertirlas en un producto cotidiano que brinda mayores posibilidades de redescubrir el cuerpo entre un mercado creciente y ávido de experimentar nuevas sensaciones.', 'Dale') ?></p>
        <p class="description_marca"><?= __('Si estás en búsqueda de romper los estereotipos y proyectar de una manera mucho más atrevida tu sensualidad, ya tienes un motivo para conectarte con Dale Más.', 'Dale') ?></p>
      </div>
      <?php
        if(!$isMexico){
          ?>
          <div class="content_team_marca content_puntos_marca">
            <h3><?= __('Nuestros Puntos de Venta', 'Dale') ?></h3>
            <div class="item_marca_team">
              <div class="item_marca_team_img">
                <img src="<?= IMGURL ?>marca/marca_5.jpeg" alt="">
              </div>
              <br>
              <p><?= __('Encuéntranos en la Calle 59 # 9 – 30 en Chapinero, Bogotá.', 'Dale') ?></p>
            </div>
            <div class="item_marca_team">
              <div class="item_marca_team_img">
                <img src="<?= IMGURL ?>marca/medello.jpeg" alt="">
              </div>
              <br>
              <p><?= __('Encuéntranos en la Calle 10 # 43 - 16, Medellín.', 'Dale') ?></p>
            </div>
          </div>
          <?php
        }
      ?>
    </div>
  </main>
<?php
get_footer();
?>