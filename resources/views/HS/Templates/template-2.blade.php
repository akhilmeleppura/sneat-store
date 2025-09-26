@extends('layouts/layoutMaster')

@section('title', 'Preview - Invoice')

@section('vendor-style')
    @vite('resources/assets/vendor/libs/flatpickr/flatpickr.scss')
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/app-invoice.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/cleave-zen/cleave-zen.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/offcanvas-add-payment.js', 'resources/assets/js/offcanvas-send-invoice.js'])
@endsection

@section('page-style')
    <style>
        @media print {

            .page-header,
            .page-footer {
                visibility: visible;
            }

            .page-header-space {
                height: 100px;
            }

            .page-footer-space {
                height: 100px;
            }

            #printMe,
            #printMe * {
                visibility: visible;
            }

            #printMe {
                position: absolute;
                left: 0;
                top: 0;
            }

            body {
                margin: 0;
            }

            button {
                display: none;
            }
        }

        .page-header {
            position: fixed;
            top: 0;
            width: 100%;
            border-bottom: 1px solid black;
            text-align: center;
            visibility: hidden;
        }

        .page-footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            border-top: 1px solid black;
            text-align: center;
            visibility: hidden;
        }

        .page-header-space,
        .page-footer-space {
            height: 0;
        }

        @page {
            margin: 20mm;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
        }

        #printMe {
            background-color: #fff;
            padding: 20px;
        }

        .content-center {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 200px);
            /* Adjust based on header and footer heights */
        }

        .tablenew {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .tablenew th,
        .tablenew td {
            border: 1px solid #ccc;
            padding: 8px;
            font-size: 14px;
            text-align: left;
        }

        .tablenew th {
            background-color: #e0e0e0;
            font-weight: bold;
        }
    </style>
@endsection

@section('content')
    <button id="printButton" class="btn btn-primary">Print Invoice</button>
    <div id="printMe">
        <div class="page-footer">
            @if ($template && $template->header_image)
                <img src="{{ asset('storage/' . $template->header_image) }}" alt="Header Image"
                    style="width: 100%; max-width: 600px;" />
            @endif
        </div>
    </div>

    <table style="width: 100%;">
        <thead>
            <tr>
                <td>
                    <div class="page-header-space"></div>
                </td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="content-center">
                        @yield('template-content')
                    </div>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td>

                    <div class="page-footer-space"></div>
                    <div class="page-header">
                        @if ($template && $template->footer_image)
                            <img src="{{ asset('storage/' . $template->footer_image) }}" alt="Footer Image"
                                style="width: 100%; max-width: 600px;" />
                        @endif
                </td>
            </tr>
        </tfoot>
    </table>
    </div>
@endsection
