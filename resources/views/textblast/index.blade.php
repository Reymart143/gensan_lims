@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4 sms-page">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="sms-hero mb-4">
                    <div>
                        <span class="sms-eyebrow"><i class="bi bi-broadcast-pin me-1"></i> NOTIFICATION CONTROL</span>
                        <h1>SMS Release Notifications</h1>
                        <p>Control whether clients receive an SMS when their laboratory test is approved for release.</p>
                    </div>
                    <div class="hero-icon"><i class="bi bi-chat-square-text"></i></div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm h-100 sms-control-card">
                            <div class="card-body p-4 p-md-5">
                                <div class="d-flex align-items-start gap-3 mb-4">
                                    <div class="status-icon {{ $isSmsActive ? 'active' : 'inactive' }}"><i
                                            class="bi {{ $isSmsActive ? 'bi-check2-circle' : 'bi-pause-circle' }}"></i>
                                    </div>
                                    <div>
                                        <p class="text-uppercase small fw-bold text-secondary mb-1"
                                            style="letter-spacing:.08em">Current status</p>
                                        <h2 class="h3 fw-bold mb-1 {{ $isSmsActive ? 'text-success' : 'text-secondary' }}">
                                            {{ $isSmsActive ? 'SMS notifications are active' : 'SMS notifications are inactive' }}
                                        </h2>
                                        <p class="text-muted mb-0">
                                            {{ $isSmsActive ? 'Clients will receive release notifications automatically.' : 'No release SMS messages will be sent to clients.' }}
                                        </p>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('text_blast.status') }}" id="smsStatusForm">
                                    @csrf
                                    <input type="hidden" name="is_active" value="{{ $isSmsActive ? 0 : 1 }}">
                                    <button type="submit"
                                        class="btn {{ $isSmsActive ? 'btn-outline-danger' : 'btn-success' }} px-4 py-2 fw-semibold"
                                        id="smsStatusButton">
                                        <i
                                            class="bi {{ $isSmsActive ? 'bi-pause-fill' : 'bi-play-fill' }} me-2"></i>{{ $isSmsActive ? 'Deactivate SMS notifications' : 'Activate SMS notifications' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm h-100 sms-info-card">
                            <div class="card-body p-4">
                                <h2 class="h5 fw-bold mb-4">How it works</h2>
                                <div class="sms-step"><span>1</span>
                                    <p>Save a routing form with an approved release date.</p>
                                </div>
                                <div class="sms-step"><span>2</span>
                                    <p>The system checks whether SMS notifications are active.</p>
                                </div>
                                <div class="sms-step"><span>3</span>
                                    <p>When active, a release message is sent to the client contact number.</p>
                                </div>
                                <div class="alert alert-light border mb-0 small"><i
                                        class="bi bi-shield-lock-fill text-primary me-2"></i>This control is enforced on the
                                    server. Turning it off stops outbound release SMS requests.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .sms-page {
            --sms-deep: #063d64;
            --sms-blue: #0879bd;
            --sms-aqua: #14b8c7;
        }

        .sms-hero {
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
            padding: 2.5rem 3rem;
            overflow: hidden;
            border-radius: 1rem;
            color: #fff;
            background: linear-gradient(120deg, var(--sms-deep), var(--sms-blue));
        }

        .sms-eyebrow {
            font-size: .74rem;
            font-weight: 800;
            letter-spacing: .1em;
            color: #9beaf0;
        }

        .sms-hero h1 {
            margin: .6rem 0;
            font-size: clamp(1.8rem, 3vw, 2.45rem);
            font-weight: 800;
        }

        .sms-hero p {
            max-width: 610px;
            margin: 0;
            color: #dcedf7;
        }

        .hero-icon {
            width: 92px;
            height: 92px;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 50%;
            background: rgba(255, 255, 255, .1);
            font-size: 2.5rem;
        }

        .sms-control-card,
        .sms-info-card {
            border-radius: 1rem;
        }

        .status-icon {
            width: 58px;
            height: 58px;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            border-radius: .9rem;
            font-size: 1.7rem;
        }

        .status-icon.active {
            color: #198754;
            background: #e4f6eb;
        }

        .status-icon.inactive {
            color: #64748b;
            background: #eef2f6;
        }

        .sms-step {
            display: flex;
            gap: .85rem;
            align-items: flex-start;
            margin-bottom: 1.1rem;
        }

        .sms-step span {
            width: 26px;
            height: 26px;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            border-radius: 50%;
            color: #fff;
            background: var(--sms-blue);
            font-size: .78rem;
            font-weight: 800;
        }

        .sms-step p {
            margin: 2px 0 0;
            color: #596f7f;
            font-size: .9rem;
            line-height: 1.45;
        }

        @media (max-width: 575px) {
            .sms-hero {
                padding: 2rem;
            }

            .hero-icon {
                display: none;
            }
        }
    </style>

    <script>
        document.getElementById('smsStatusForm').addEventListener('submit', function(event) {
            const activating = this.querySelector('[name="is_active"]').value === '1';
            const message = activating ?
                'Activate SMS release notifications? Clients will receive an SMS when their test is released.' :
                'Deactivate SMS release notifications? No client release SMS messages will be sent.';

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    </script>
@endsection
