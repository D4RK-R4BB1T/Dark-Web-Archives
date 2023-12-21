<div class="col-md-12">
    {{-- Feedback --}}
    @if($purchase -> isDelivered() && $purchase -> isBuyer() && !$purchase -> hasFeedback())
        <tr>
            <td colspan="2" class="">
                <h4>Leave feedback</h4>
                <form action="{{ route('profile.purchases.feedback.new', $purchase) }}" method="POST">
                    {{ csrf_field() }}
                    <div class="form-row">
                        <div class="col-md-3 text-left">
                            <label for="quality_rate">Quality:</label>
                            <select name="quality_rate" id="quality_rate" class="form-control">
                                @for($i=1; $i<=5; $i++)
                                    <option value="{{ $i }}">
                                        {{ $i }} {{ str_plural('star', $i) }}
                                    </option>
                                @endfor
                            </select>

                            <label for="communication_rate">Communication:</label>
                            <select name="communication_rate" id="communication_rate" class="form-control">
                                @for($i=1; $i<=5; $i++)
                                    <option value="{{ $i }}">
                                        {{ $i }} {{ str_plural('star', $i) }}
                                    </option>
                                @endfor
                            </select>
                            <label for="shipping_rate">Shipping:</label>
                            <select name="shipping_rate" id="shipping_rate" class="form-control">
                                @for($i=1; $i<=5; $i++)
                                    <option value="{{ $i }}">
                                        {{ $i }} {{ str_plural('star', $i) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="type">Type:</label>
                            <select name="type" id="type" class="form-control">
                                <option value="positive">Positive</option>
                                <option value="neutral" selected>Neutral</option>
                                <option value="negative">Negative</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="comment">Comment:</label>
                            <textarea name="comment" id="comment" rows="5" class="form-control"
                                      placeholder="Place your comment here"></textarea>
                            <button type="submit" class="btn btn-block btn-outline-primary mt-2">Leave
                                feedback
                            </button>
                        </div>
                    </div>


                </form>
            </td>
        </tr>

    @elseif($purchase -> isDelivered() && $purchase -> hasFeedback())
        <tr>
            <td colspan="2">
                <h4>Feedback by buyer</h4>
                <ul class="list-group">
                    <li class="list-group-item">
                        Qualtiy:
                        @include('includes.purchases.stars', ['stars' => $purchase -> feedback -> quality_rate])
                    </li>
                    <li class="list-group-item">
                        Shipping:
                        @include('includes.purchases.stars', ['stars' => $purchase -> feedback -> shipping_rate])
                    </li>
                    <li class="list-group-item">
                        Communication:
                        @include('includes.purchases.stars', ['stars' => $purchase -> feedback -> communication_rate])
                    </li>
                    <li class="list-group-item">
                        Type:
                        {{ $purchase->feedback->getType() }}
                    </li>
                    <li class="list-group-item text-center">
                        {{ $purchase -> feedback -> comment }}
                    </li>
                </ul>
            </td>
        </tr>
    @endif
</div>