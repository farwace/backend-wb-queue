@php
    $fieldId = uniqid('test-send');
@endphp

@if(!empty($badgeCode) && !empty($url) && !empty($directionCode))
    <label for="<?=$fieldId?>"><?=!empty($label) ? ($label . ': '): ''?></label>
    <div class="row">
        <button class="btn btn-primary" id="<?=$fieldId?>-submit" type="button">Встать в очередь</button>
    </div>


    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function (){
            const searchUrl = {{ Js::from($url ?? '') }};
            const badgeCode = {{ Js::from($badgeCode ?? '') }};
            const directionCode = {{ Js::from($directionCode ?? '') }};
            const testSendButtonId = {{ Js::from($fieldId . '-submit') }};


            $('#' + testSendButtonId).on('click', function (){
                $('#' + testSendButtonId).attr('disabled', true);
                sendEnterQueueAction(searchUrl, badgeCode, directionCode).then((res) => {
                    swal({
                        title: `Успех`,
                        text: 'На очереди!',
                        buttons: {
                            cancel: {
                                text: 'Закрыть',
                                value: null,
                                className: 'bg-success',
                                closeModal: true,
                                visible: true,
                            },
                        },
                        dangerMode: false,
                    })
                }).catch(err => {
                    swal({
                        title: `Ошибка`,
                        text: err?.responseJSON?.message || '???',
                        icon: 'warning',
                        buttons: {
                            cancel: {
                                text: 'Закрыть',
                                value: null,
                                className: 'bg-secondary',
                                closeModal: true,
                                visible: true,
                            },
                        },
                        dangerMode: true,
                    })
                    $('#' + testSendButtonId).attr('disabled', false);
                }).finally(() => {
                    $('#' + testSendButtonId).attr('disabled', false);
                })
            })


            const sendEnterQueueAction = (strSearchUrl, strBadgeCode, strDirectionCode) => {
                return new Promise((resolve, reject) => {
                    $.ajax({
                        url: strSearchUrl,
                        type: 'POST',
                        beforeSend: function(request) {
                            request.setRequestHeader("badge-code", strBadgeCode);
                        },
                        success: resolve,
                        error: reject,
                        data: {
                            direction: strDirectionCode
                        }
                    })
                })
            }
        });

    </script>
@endif
