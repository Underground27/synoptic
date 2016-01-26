$( document ).ready(function() {	

	var editDefaultAction = $('#edit-form').attr('action');
	
	if(Cookies.get('lang') == 'undefined'){
		var lang = 'ua';
		Cookies.set('lang', lang)
	}else{
		var lang = Cookies.get('lang');
	}
	
	console.log(Cookies.get('lang'));
	
	//Отправить запрос на получение данных
	$.ajax({
		url: 'api/sources/',
		type: 'GET',
		data: {
			'_locale': lang
		},
		error: function(result) {
			if(result.responseJSON){
				text = JSON.stringify(result.responseJSON, null, '\t');
			}else{
				text = result.responseText;
			}
			
			$('#responce-code').text(result.status);
			$('#responce-text').val(text);
		},
		success: function(response, status, xhr) {
			
			if(xhr.responseJSON){
				text = JSON.stringify(xhr.responseJSON, null, '\t');
			}else{
				text = xhr.responseText;
			}
			
			$('#responce-code').text(xhr.status);
			$('#responce-text').val(text);
			
			//Если произошла ошибка
			if(response.status == 'error') return;
			
			//Добавить данные в таблицу
			$.each(response.data, function( i, item ){
				var row = $('<tr class="data-row">')
				
				row.append($('<td class="id">' + item.id + '</td>'));
				row.append($('<td class="name">' + item.name + '</td>'));
				row.append($('<td class="lat">' + item.lat + '</td>'));
				row.append($('<td class="lon">' + item.lon + '</td>'));
				row.append($('<td class="controls"><button type="button" class="btn btn-primary btn-sm btn-edit" type="Редактировать"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></button><button type="button" class="btn btn-primary btn-sm btn-delete" type="Удалить"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button></td>'));
				
				$("#data-table").append(row);
			});
			
			$('.btn-edit').click(function(){
				//Получить содержимое полей строки таблицы
				var id = $(this).parents('.data-row').find('td.id').text();
				
				//Добавить ID к action формы
				$('#edit-form').attr('action', editDefaultAction + id);
				
				//Отправить запрос на получение данных объекта
				$.ajax({
					url: 'api/sources/' + id,
					type: 'GET',
					error: function(result) {
						if(result.responseJSON){
							text = JSON.stringify(result.responseJSON, null, '\t');
						}else{
							text = result.responseText;
						}
						
						$('#responce-code').text(result.status);
						$('#responce-text').val(text);
					},
					success: function(response, status, xhr) {
						
						if(xhr.responseJSON){
							text = JSON.stringify(xhr.responseJSON, null, '\t');
						}else{
							text = xhr.responseText;
						}
						
						$('#responce-code').text(xhr.status);
						$('#responce-text').val(text);
						
						//Заполнение полей формы
						$('#edit-form input#edit-name').val(response.data.name);
						$('#edit-form input#edit-lat').val(response.data.lat);
						$('#edit-form input#edit-lon').val(response.data.lon);
						
					}
				});
				
				//Скроллить страницу к форме редактирования
				$('html, body').animate({
					scrollTop: $("#edit-form").offset().top
				}, 300);
			});
			
			$('.btn-delete').click(function(){
				//Получить id строки таблицы
				var row = $(this).parents('.data-row');
				var id = $(row).find('td.id').text();
				//Отправить запрос на удаление				
				$.ajax({
					url: 'api/sources/' + id,
					type: 'DELETE',
					complete: function(){
						//Скроллить страницу к ответу сервера
						$('html, body').animate({
							scrollTop: $("#responce").offset().top
						}, 300);
					},
					error: function(result) {
						if(result.responseJSON){
							text = JSON.stringify(result.responseJSON, null, '\t');
						}else{
							text = result.responseText;
						}
						
						$('#responce-code').text(result.status);
						$('#responce-text').val(text);
					},
					success: function(response, status, xhr) {
						
						if(xhr.responseJSON){
							text = JSON.stringify(xhr.responseJSON, null, '\t');
						}else{
							text = xhr.responseText;
						}
						
						$('#responce-code').text(xhr.status);
						$('#responce-text').val(text);				
						
						//Если удаление прошло успешно - удалить строку из таблицы
						$(row).remove();
					}
				});
			});
			
		}
	});
	
	$('#lang-selector a').click(function(){
		lang = $(this).attr('data-lang');
		Cookies.set('lang', lang);
		location.reload();
	});
	
	//Cброс формы редактирования
	$('#edit-form .btn-reset').click(function(){
		$('#edit-form').attr('action', editDefaultAction);
	});
	
	//AJAX отправка форм для демонстрации ответа сервера
	$('#add-form, #edit-form').submit(function(e){
		
		 e.preventDefault();
		
		var form = $(e.target);
		
		$.ajax({
			url: form.attr('action'),
			type: 'POST',
			data: form.serialize(),
			complete: function(){
				//Скроллить страницу к ответу сервера
				$('html, body').animate({
					scrollTop: $("#responce").offset().top
				}, 300);
			},
			error: function(result) {
				if(result.responseJSON){
					text = JSON.stringify(result.responseJSON, null, '\t');
				}else{
					text = result.responseText;
				}
				
				$('#responce-code').text(result.status);
				$('#responce-text').val(text);
			},
			success: function(response, status, xhr) {
				
				if(xhr.responseJSON){
					text = JSON.stringify(xhr.responseJSON, null, '\t');
				}else{
					text = xhr.responseText;
				}
				
				$('#responce-code').text(xhr.status);
				$('#responce-text').val(text);
			}
		});		
	});
	
});