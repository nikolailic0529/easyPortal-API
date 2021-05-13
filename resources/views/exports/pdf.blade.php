<html>
    <body>
        <table>
            @foreach ($rows as $index => $row)
                <tr>
                    @foreach ($row as $col)
                        @if($index == 0)
                            <td style='font-weight:bold;'> {{ $col }}</td>
                        @else
                            <td>{{ $col }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </table>
    </body>
</html>