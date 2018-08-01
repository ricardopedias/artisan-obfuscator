<?php

    function procedure_mixed_one()
    {
        return "Teste de retorno um";
    }

?><!doctype html>
<html>
    <head>
        <title>Código misto ofuscado</title>
    </head>

    <body>
        <?php

            /**
             * Comentário da função
             * @return string
             */
            function procedure_mixed_two()
            {
                return "Teste de retorno dois";
            }

        ?>

        <h1>Teste de ofuscação <?= 'composta' ?></h1>

        <p>
            <?= procedure_mixed_one() ?>
        </p>

        <br><br><br>

        <p>
            <?= procedure_mixed_two() ?>
        </p>

    </body>
</html>
