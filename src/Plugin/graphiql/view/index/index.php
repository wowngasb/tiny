<?php
/** @var string $json_api_list */
/** @var string $tool_title */
/** @var \Tiny\Interfaces\RequestInterface $request */
/** @var string $tool_title */

?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($tool_title)?></title>
    <link rel="stylesheet" href="<?= \Tiny\Application::url($request, ['', 'assets', 'graphiql.css']) ?>"/>

    <script src="<?= \Tiny\Application::url($request, ['', 'assets', 'fetch.min.js']) ?>"></script>
    <script src="<?= \Tiny\Application::url($request, ['', 'assets', 'react.min.js']) ?>"></script>
    <script src="<?= \Tiny\Application::url($request, ['', 'assets', 'react-dom.min.js']) ?>"></script>
    <script src="<?= \Tiny\Application::url($request, ['', 'assets', 'graphiql.js']) ?>"></script>
    <script src="<?= \Tiny\Application::url($request, ['', 'assets', 'jquery-1.7.2.min.js']) ?>"></script>
    <script src="<?= \Tiny\Application::url($request, ['', 'assets', 'apihub.js']) ?>"></script>
    <style>
        body {
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }
        #splash {
            color: #333;
            display: flex;
            flex-direction: column;
            font-family: system, -apple-system, "San Francisco", ".SFNSDisplay-Regular", "Segoe UI", Segoe, "Segoe WP", "Helvetica Neue", helvetica, "Lucida Grande", arial, sans-serif;
            height: 100vh;
            justify-content: center;
            text-align: center;
        }
        #graphql-box{
            height: 100%;
        }
    </style>
</head>
<body>
<div id="graphql-box" ></div>
<script type="text/javascript">
    $(function(){
        //$('#graphql-box').height( $('body').height() - $('#graphql-token').height() );
    });

    // Parse the search string to get url parameters.
    var search = window.location.search;
    var parameters = {};
    search.substr(1).split('&').forEach(function (entry) {
        var eq = entry.indexOf('=');
        if (eq >= 0) {
            parameters[decodeURIComponent(entry.slice(0, eq))] =
                decodeURIComponent(entry.slice(eq + 1));
        }
    });

    // if variables was provided, try to format it.
    if (parameters.variables) {
        try {
            parameters.variables =
                JSON.stringify(JSON.parse(parameters.variables), null, 2);
        } catch (e) {
            // Do nothing, we want to display the invalid JSON as a string, rather
            // than present an error.
        }
    }

    // When the query and variables string is edited, update the URL bar so
    // that it can be easily shared
    function onEditQuery(newQuery) {
        parameters.query = newQuery;
        updateURL();
    }

    function onEditVariables(newVariables) {
        parameters.variables = newVariables;
        updateURL();
    }

    function updateURL() {
        var newSearch = '?' + Object.keys(parameters).map(function (key) {
                return encodeURIComponent(key) + '=' +
                    encodeURIComponent(parameters[key]);
            }).join('&');
        history.replaceState(null, '', newSearch);
    }

    function replaceAll(str, s1, s2){
        if( typeof s1 === 'string' && typeof s2 === 'string' ){
            str = str.replace(new RegExp(s1, "gm"), s2);
        } else if( typeof s1 === 'object' && typeof s2 === 'object' ) {
            var len = s1.length <= s2.length ? s1.length : s2.length;
            for(var idx = 0; idx < len; idx++){
                str = str.replace(new RegExp(s1[idx], "gm"), s2[idx]);
            }
        }
        return str;
    }

    var dmsObj = new ApiHub({
        debug: true
    });
    // Defines a GraphQL fetcher using the fetch API.
    function graphQLFetcher(graphQLParams) {
        graphQLParams.query = replaceAll(graphQLParams.query, ["\n"], [""]);
        if( graphQLParams.variables && typeof graphQLParams.variables === 'string'){
            graphQLParams.variables = JSON.parse(graphQLParams.variables);
        }
        graphQLParams.token = $('input[name=token]').val();
        return new Promise(function (resolve, reject) {
            dmsObj.api_ajax(
                location.hostname,
                '/api/GraphQLApi/exec',
                JSON.stringify(graphQLParams),
                function (res) {
                    resolve(res);
                },
                function (error) {
                    resolve(error);
                }, null, null, {
                    contentType: "application/json"
                }
            );
        });
    }

    // Render <GraphiQL /> into the body.
    ReactDOM.render(
        React.createElement(GraphiQL, {
            fetcher: graphQLFetcher,
            query: parameters.query,
            variables: parameters.variables,
            onEditQuery: onEditQuery,
            onEditVariables: onEditVariables
        }),
        document.getElementById('graphql-box')
    );
</script>
</body>
</html>
