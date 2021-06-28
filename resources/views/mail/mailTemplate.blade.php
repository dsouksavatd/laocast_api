@include('mail.header', [
    'name' => @$params['name'],
    'body' => @$params['body']
])

@if($params['action'])
<table border="0" cellpadding="0" cellspacing="0" class="btn btn-primary" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; box-sizing: border-box;">
    <tbody>
      <tr>
        <td align="left" style="font-family: sans-serif; font-size: 14px; vertical-align: top; padding-bottom: 15px;">
          <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: auto;">
            <tbody>
              <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; background-color: #F9CB32; border-radius: 5px; text-align: center;"> <a href="{{ @$params['target'] }}" target="_blank" style="display: inline-block; color: #000000; background-color: #F9CB32; border: solid 1px #F9CB32; border-radius: 5px; box-sizing: border-box; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: bold; margin: 0; padding: 12px 25px; text-transform: capitalize; border-color: #F9CB32;">{{ @$params['action'] }}</a> </td>
              </tr>
            </tbody>
          </table>
        </td>
      </tr>
    </tbody>
</table>
@endif

@include('mail.footer', [
  'footer' => @$params['footer']
])