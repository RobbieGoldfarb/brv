import React from 'react';
import ReactDOM from 'react-dom';
import classNames from 'classnames';

import DataLayer from 'forms/DataLayer';

import Dialog, { DialogButton, DialogButtonActions } from 'dialogs/Dialog';
import Form, { Group as FormGroup, Label as FormLabel, Button, ErrorMessage } from 'forms/Form';

import { Link } from 'components/Link';
import AspectInputField from 'forms/AspectInputField';

const AspectInputFieldLinked = props => (
    <AspectInputField
        types={props.data.aspect_types || []}
        name='aspect'
        custom={true}
        placeHolder="type something here"
    />
);

export default class NewAspectDialog extends React.Component {
    constructor() {
        super();

        this.state = {
            createError: null
        };

        this.submitError = this.submitError.bind(this);
    }

    componentWillUnmount() {
        this._unmounted = true;
    }

    submitError(error) {
        if (this._unmounted) return;
        this.setState({ createError: error.data.reason || `Unknown error: ${error.status}` });
    }

    render() {
        return (
            <Dialog
                defaultShow={this.props.show}
                onAction={this.props.onAction}
                escapable={true}
            >
                <Form
                    method="POST"
                    action="/api/aspect"
                    data={{ store: this.props.storeId }}
                    onSuccess={()=>this.props.onAction(DialogButtonActions.SUCCESS)}
                    onError={this.submitError}
                >
                    { this.state.createError !== null && (
                        <ErrorMessage text={this.state.createError} />
                    ) }

                    <FormGroup className='new-aspect'>
                        <FormLabel
                            text={'How satisfied are customers with'}
                            inline={true}
                        />
                        <DataLayer
                            action="/api/aspecttypes/industry"
                            data={{exclude_store: this.props.storeId}}
                        >
                            <AspectInputFieldLinked />
                        </DataLayer>
                        <FormLabel
                            text={'?'}
                            inline={true}
                        />
                    </FormGroup>
                    <FormGroup className='dialog-controls link-style'>
                        <Button
                            label='Add Question'
                            submit={true}
                            right={true}
                        />
                        <Button
                            label='Cancel'
                            onClick={()=>this.props.onAction(DialogButtonActions.CLOSE)}
                            right={true}
                        />
                    </FormGroup>
                </Form>
            </Dialog>
        );
    }
}
