<!DOCTYPE html>
<html>
    <head>
        <title><?PHP ECHO MaxfsErrorPageTitle; ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style>
            .main{
                border-width: 2px;
                border-color: red;
                margin-top: 50px;
                margin-left: auto;
                margin-right: auto;
                width: 600px;
            }
            #string{
                font-size: 24px;
                font-family: monospace,sans-serif;
                text-align: center;
            }
            #string > p{
                font-size: 72px;
                margin-top: 80px;
                margin-bottom: 30px;
            }
        </style>
    </head>
    <body>
        <div class="main">
            <div id="string"><p>:( </p><?PHP ECHO MaxfsErrorPageInfo; ?></div>
        </div>
    </body>
</html>
