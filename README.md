# LCEB
Le Compte Est Bon (PHP)

Deux scripts PHP reposant sur ma classe \xylian\graph.

Il s'agit d'une recherche **a priori** exhaustive dans l'arbre des possibilités.
Chacun peut être lancé :
* soit en ligne de commande ```shell php lceb*.php <target> {<number>} [-delta=N | -inter=1] [-withPower]```

  * Les premiers paramètres sont explicites, _inter_ signifie 'interactif' au cas où _delta_ n'est pas spécifié, et _withPower_ indique si l'on autorise l'opération *puissance*.

* soit comme script web, en AJAX ou non (auquel cas il faut donner le paramètre ajax=1), et les paramètres sont :
  * lceb\*.php?target&nombre1_nombre1_nombre2...[&delta=N][&withPower]

Une démo est visible [sur cette page](https://www.xylian.fr/LCEB).
