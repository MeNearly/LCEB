# LCEB
Le Compte Est Bon (PHP)

## Deux scripts PHP reposant sur ma classe \xylian\graph.

Il s'agit d'une recherche **a priori** exhaustive dans l'arbre des possibilités.
L'algorithme peut trouver plusieurs solutions, mais :
* Si le nombre d'opérations minimum est le maxmum possible (par exemple 5 pour 6 'tuiles'),
  il ne renverra qu'un seule d'entre elles

* Sinon, il renverra la plus courte ET la plus longue

Chacun des scripts peut être lancé :
* soit en ligne de commande ```php lceb*.php <target> {<number>} [-delta=N | -inter=1] [-withPower]```

  * Les premiers paramètres sont explicites, _inter_ signifie 'interactif' au cas où _delta_ n'est pas spécifié, et _withPower_ indique si l'on autorise l'opération *puissance*.
    Dans le cas d'un delta spécifié (soit en paramètre, soit en mode interactif), seule la première solution trouvée avec un distance <= delta est renvoyée.

* soit comme script web, en AJAX ou non (auquel cas il faut donner le paramètre ajax=1), et les paramètres sont :
  * lceb\*.php?target&nombre1_nombre1_nombre2...[&delta=N][&withPower]

## Pourquoi deux scripts ?

* ```lceb.php```
Renvoie la ou les solutions avec une décomposition *pas-à-pas* des opérations.
* ```lceb_formulas.php```
Renvoie la ou les solutions sous la forme d'une seule opération

Une démo est visible [sur cette page](https://www.xylian.fr/LCEB).
