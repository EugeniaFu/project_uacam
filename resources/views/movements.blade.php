<html>
<body>
	<h2>
		Movimientos{{ !empty($onlyInventory) ? ' de Inventario' : '' }} - Página {{ $page }}
	</h2>

	<table border='1' cellpadding='6'>
		<tr>
			<th>ID</th>
			<th>Fecha</th>
			<th>Usuario</th>
			<th>Acción</th>
			<th>Producto</th>
			<th>Antes</th>
			<th>Cambio</th>
			<th>Después</th>
			<th>Motivo</th>
			<th>Observaciones</th>
		</tr>

		@foreach($rows as $r)
			<tr>
				<td>{{ $r->id }}</td>
				<td>{{ $r->created_at }}</td>
				<td>{{ $r->user }}</td>
				<td>{{ $r->action }}</td>
				<td>{{ $r->product_name ?? '' }}</td>
				<td>{{ $r->quantity_before ?? '' }}</td>
				<td>{{ $r->quantity_change ?? '' }}</td>
				<td>{{ $r->quantity_after ?? '' }}</td>
				<td>{{ $r->baja_motivo ?? '' }}</td>
				<td>
					@if(!empty($r->alta_observaciones))
						{{ $r->alta_observaciones }}
					@elseif(!empty($r->baja_observaciones))
						{{ $r->baja_observaciones }}
					@else
						{{ $r->details ?? '' }}
					@endif
				</td>
			</tr>
		@endforeach
	</table>
</body>
</html>