<html>
    <body>
        <table>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($row as $col)
                        <td>{{ $col }}</td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    </body>
</html>