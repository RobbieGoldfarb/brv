import React, { Component } from 'react';
import PropTypes from 'prop-types';
import Form from 'forms/Form';
import classNames from 'classnames';
import { Link } from 'forms/inputs/Button';
import { Mood } from 'utils/Mood';

/**
 * Event aspect deletion form.
 *
 * @param {object} props
 * @param {number} props.id The id of the event aspect to delete.
 * @param {number} props.eventId The id of the event.
 * @param {function} props.onRemove onRemove event handler.
 */
const DeleteAspect = props => (
    <Form
        action={`/api/event/${props.eventId}/aspect/${props.id}`}
        method="DELETE"
        onSuccess={props.onRemove}
        onError={props.onError}
        onBegin={props.onBeginRemove}>
        <Link label='Remove' submit={true} danger={true} />
    </Form>
);

/**
 * Details for an event aspect.
 *
 * @param {object} props
 * @param {string} props.className Custom classname to apply to details element.
 * @param {number} props.responses The number of feedback responses.
 * @param {number} props.change The percent change over the course of the event.
 */
const AspectDetails = props => (
    <div className={'detail ' + props.className || ''}>
        { (props.responses > 0 && (
            <span title={
                (props.change > 0 ? '+' : (props.change == 0 ? '' : '-')) + Math.abs(+props.change.toFixed(2)) + '%'
                + ` after ${props.responses} response${props.responses>1?'s':''}`
            }>
                <span className={'change ' + Mood(props.change, -100)}>{
                    (props.change > 0 ? '+' : (props.change == 0 ? '' : '-')) + Math.abs(+props.change.toFixed(2)) + '%'
                }</span>
                <span className='text'>
                    {` after ${props.responses} response${props.responses>1?'s':''}`}
                </span>
            </span>
        )) || (
            <span className='text' title={"no activity for this aspect"}>
                {"no activity for this aspect"}
            </span>
        )}
    </div>
);

/**
 * Individual event aspect.
 *
 * @param {object} props
 * @param {string} props.title The name of the aspect.
 * @param {number} props.responses The number of feedback responses.
 * @param {number} props.change The percent change over the course of the event.
 * @param {number} props.eventId The id of the event.
 * @param {number} props.id The id of the event aspect to delete.
 * @param {function} props.onRemove onRemove event handler.
 */
class EventAspectsItem extends Component {
    static propTypes = {
        title: PropTypes.string,
        responses: PropTypes.number,
        change: PropTypes.number,
        eventId: PropTypes.number,
        id: PropTypes.number,
        onRemove: PropTypes.func
    };

    constructor() {
        super();

        this.state = {
            removing: false
        };

        this.onBeginRemove = ::this.onBeginRemove;
        this.onRemoveError = ::this.onRemoveError;
    }

    onBeginRemove() {
        this.setState({ removing: true });
    }

    onRemoveError() {
        this.setState({ removing: false });
    }

    render () {
        return (
            <div className={classNames('event-aspects-item', {
                'state-removing': this.state.removing
            })}>
                <div className='ly ly-abs-container'>
                    <div
                        className='title left'
                        title={this.state.removing ? '' : this.props.title}>
                        {this.props.title}
                    </div>
                    <AspectDetails
                        className='left'
                        responses={this.props.responses}
                        change={this.props.change}
                    />
                    <div className='control right'>
                        <DeleteAspect
                            eventId={this.props.eventId}
                            id={this.props.id}
                            onRemove={this.props.onRemove}
                            onBeginRemove={this.onBeginRemove}
                            onError={this.onRemoveError}
                        />
                    </div>
                </div>
            </div>
        );
    }
}

export { EventAspectsItem };
